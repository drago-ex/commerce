<?php

declare(strict_types=1);

namespace Drago\Commerce\UI\Order;

use Brick\Money\Exception\MoneyMismatchException;
use Brick\Money\Money;
use Brick\PhoneNumber\PhoneNumber;
use Brick\PhoneNumber\PhoneNumberFormat;
use DateTimeImmutable;
use Dibi\DriverException;
use Dibi\Exception;
use Drago\Attr\AttributeDetectionException;
use Drago\Commerce\Domain\Customer\Customer;
use Drago\Commerce\Domain\Customer\CustomerRepository;
use Drago\Commerce\Domain\Order\OrderProductRepository;
use Drago\Commerce\Domain\Order\OrderRepository;
use Drago\Commerce\Domain\Product\ProductRepository;
use Drago\Commerce\Event\EventDispatcher;
use Drago\Commerce\Event\OrderPlaced;
use Drago\Commerce\Service\DiscountCodeService;
use Drago\Commerce\Service\OrderSession;
use Drago\Commerce\Service\ShoppingCartSession;
use Drago\Commerce\UI\BaseControl;
use Nette\Application\UI\Form;
use Tracy\Debugger;


/**
 * @property-read SummaryOrderTemplate $template
 */
class SummaryOrderControl extends BaseControl
{
	public function __construct(
		private readonly ShoppingCartSession $shoppingCartSession,
		private readonly OrderSession $orderSession,
		private readonly OrderRepository $orderRepository,
		private readonly OrderProductRepository $orderProductsRepository,
		private readonly CustomerRepository $customerRepository,
		private readonly ProductRepository $productRepository,
		private readonly EventDispatcher $eventDispatcher,
		private readonly DiscountCodeService $discountCodeService,
	) {
	}


	/**
	 * @throws MoneyMismatchException
	 * @throws AttributeDetectionException
	 * @throws Exception
	 */
	public function render(): void
	{
		$template = $this->template;
		$template->setFile($this->templateControl ?: __DIR__ . '/Summary.latte');
		$template->setTranslator($this->translator);
		$template->shoppingCart = $this->shoppingCartSession->getItems();
		$template->amountItems = $this->shoppingCartSession->getAmountItems();
		$template->subtotalPrice = $this->shoppingCartSession->getSubtotalPrice();
		$template->discountAmount = $template->subtotalPrice->minus($this->shoppingCartSession->getTotalPrice());
		$template->discountCode = $this->discountCodeService->getCode()?->code;
		$template->totalPrice = $this->getTotalPrice();
		$template->carrier = $this->getOrderItem('carrier');
		$template->customer = $this->getOrderItem('customer');
		$template->payment = $this->getOrderItem('payment');
		$template->breadcrumbs = $this->getBreadcrumbs();
		$template->render();
	}


	private function getOrderItem(string $name): mixed
	{
		$items = $this->orderSession->getItems();
		return $items->{$name} ?? null;
	}


	/**
	 * @throws MoneyMismatchException
	 */
	private function getTotalPrice(): Money
	{
		return $this->shoppingCartSession->getTotalPrice()
			->plus($this->orderSession->getCarrierPrice())
			->plus($this->orderSession->getPaymentPrice());
	}


	private function getAmountPrice(Money $money): float
	{
		return $money->getAmount()
			->toFloat();
	}


	protected function createComponentSendOrder(): Form
	{
		$form = new Form;
		$form->addSubmit('send', 'Confirm the purchase');
		$form->onSuccess[] = $this->processOrder(...);
		return $form;
	}


	/**
	 * @throws DriverException
	 */
	public function processOrder(Form $form): void
	{
		$order = $this->orderSession->getItems();
		$customer = $order->customer;
		$carrier = $order->carrier;
		$payment = $order->payment;

		if ($customer === null || $carrier === null || $payment === null) {
			$form->addError('Order details are incomplete.');
			return;
		}

		try {
			$this->orderRepository->getConnection()->begin();

			// Save the customer.
			$phoneStr = $customer->phone instanceof PhoneNumber
				? $customer->phone->format(PhoneNumberFormat::INTERNATIONAL)
				: (string) $customer->phone;

			$customerData = new Customer(
				email: $customer->email,
				phone: $phoneStr,
				name: $customer->name,
				surname: $customer->surname,
				street: $customer->street,
				city: $customer->city,
				postal_code: $customer->postal_code,
				country: $customer->country,
				note: $customer->note,
			);
			$this->customerRepository->save((array) $customerData);

			// Save order.
			$subtotalPrice = $this->shoppingCartSession->getSubtotalPrice();
			$discountAmount = $subtotalPrice->minus($this->shoppingCartSession->getTotalPrice());
			$discountCode = $this->discountCodeService->getCode()?->code;

			$orderData = new OrderSummary(
				customer_id: $this->customerRepository->getInsertId(),
				carrier_id: $carrier->id,
				payment_id: $payment->id,
				carrier_price: $this->getAmountPrice($carrier->price),
				payment_price: $this->getAmountPrice($payment->price),
				subtotal_price: $this->getAmountPrice($subtotalPrice),
				total_price: $this->getAmountPrice($this->getTotalPrice()),
				discount_code: $discountCode,
				discount_amount: $this->getAmountPrice($discountAmount),
				created_at: new DateTimeImmutable,
			);

			$this->orderRepository->save((array) $orderData);

			$orderId = $this->orderRepository->getInsertId();
			foreach ($this->shoppingCartSession->getItems() as $item) {
				$product = $this->productRepository->getOne($item->product->id);
				if ($product === null) {
					throw new \Exception("Product with ID {$item->product->id} not found.");
				}

				// Atomically check-and-deduct inventory in a single SQL
				// statement, so two concurrent orders can never both
				// succeed for the same last unit of stock.
				$amount = $item->amount->toInt();
				if (!$this->productRepository->decrementStock($product->id, $amount)) {
					throw new \Exception("The product '$product->name' is not in stock in the requested quantity.");
				}

				//Save order products.
				$orderProduct = new OrderProduct(
					order_id: $orderId,
					product_id: $item->product->id,
					amount: $amount,
					unit_price: $this->getAmountPrice($item->product->price),
				);
				$this->orderProductsRepository->insert((array) $orderProduct)->execute();
			}

			if (!$this->discountCodeService->consume()) {
				throw new \Exception('The discount code has just reached its usage limit, please try again without the code.');
			}

			$this->orderRepository->getConnection()->commit();

		} catch (\Throwable $e) {
			$this->orderRepository->getConnection()->rollback();
			Debugger::barDump($e);
			$form->addError('An error occurred while processing your order: ' . $e->getMessage());
			return;
		}

		$this->eventDispatcher->dispatch(
			new OrderPlaced(
				orderId: $orderId,
				orderSummary: $orderData,
				customer: $customer,
				carrier: $carrier,
				payment: $payment,
				shoppingCartSession: $this->shoppingCartSession,
			),
		);

		$this->shoppingCartSession->remove();
		$this->discountCodeService->remove();
		$this->orderSession->remove();
		$this->getPresenter()->redirect($this->linkRedirectTarget);
	}
}
