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
use Drago\Commerce\Domain\Product\ProductEntity;
use Drago\Commerce\Domain\Product\ProductRepository;
use Drago\Commerce\Event\EventDispatcher;
use Drago\Commerce\Event\OrderPlaced;
use Drago\Commerce\Service\OrderSession;
use Drago\Commerce\Service\DiscountCodeService;
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
	 */
	public function render(): void
	{
		$template = $this->template;
		$template->setFile($this->templateControl ?: __DIR__ . '/Summary.latte');
		$template->shoppingCart = $this->shoppingCartSession->getItems();
		$template->amountItems = $this->shoppingCartSession->getAmountItems();
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
		$money->isZero();
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
	 * @throws MoneyMismatchException
	 * @throws DriverException
	 * @throws \Exception
	 */


	/**
	 * @throws MoneyMismatchException
	 * @throws DriverException
	 * @throws \Exception
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
			$orderData = new OrderSummary(
				customer_id: $this->customerRepository->getInsertId(),
				carrier_id: $carrier->id,
				payment_id: $payment->id,
				carrier_price: $this->getAmountPrice($carrier->price),
				payment_price: $this->getAmountPrice($payment->price),
				total_price: $this->getAmountPrice($this->getTotalPrice()),
				created_at: new DateTimeImmutable,
			);

			$this->orderRepository->save((array) $orderData);

			$orderId = $this->orderRepository->getInsertId();
			foreach ($this->shoppingCartSession->getItems() as $item) {
				$product = $this->productRepository->getOne($item->product->id);
				if ($product === null) {
					throw new \Exception("Product with ID {$item->product->id} not found.");
				}

				if ($product->stock < $item->amount->toInt()) {
					throw new \Exception("The product '{$product->name}' is not in stock in the requested quantity.");
				}

				// Deduct inventory.
				$newStock = $product->stock - $item->amount->toInt();
				$productEntity = new ProductEntity;
				$productEntity->id = $product->id;
				$productEntity->stock = $newStock;
				$this->productRepository->save($productEntity);

				//Save order products.
				$orderProduct = new OrderProduct(
					order_id: $orderId,
					product_id: $item->product->id,
					amount: $item->amount->toInt(),
				);
				$this->orderProductsRepository->save((array) $orderProduct);
			}

			$this->discountCodeService->consume();
			$this->orderRepository->getConnection()->commit();
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

		} catch (Exception | AttributeDetectionException $e) {
			$this->orderRepository->getConnection()->rollback();
			Debugger::barDump($e);
		}
	}
}
