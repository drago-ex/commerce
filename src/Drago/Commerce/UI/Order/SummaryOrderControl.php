<?php

declare(strict_types=1);

namespace Drago\Commerce\UI\Order;

use Brick\Money\Exception\MoneyMismatchException;
use Brick\Money\Money;
use Brick\PhoneNumber\PhoneNumber;
use Brick\PhoneNumber\PhoneNumberFormat;
use DateTimeImmutable;
use Dibi\Exception;
use Drago\Attr\AttributeDetectionException;
use Drago\Commerce\Commerce;
use Drago\Commerce\Domain\Customer\Customer;
use Drago\Commerce\Domain\Customer\CustomerRepository;
use Drago\Commerce\Domain\Order\ItemUnavailableException;
use Drago\Commerce\Domain\Order\OrderException;
use Drago\Commerce\Domain\Order\OrderProductRepository;
use Drago\Commerce\Domain\Order\OrderRepository;
use Drago\Commerce\Domain\Order\OutOfStockException;
use Drago\Commerce\Domain\Order\StockReservation;
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
		private readonly StockReservation $stockReservation,
		private readonly Commerce $commerce,
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
		$template->originalPrice = $this->shoppingCartSession->getOriginalPrice();
		$template->subtotalPrice = $this->shoppingCartSession->getSubtotalPrice();
		$template->productDiscountAmount = $template->originalPrice->minus($template->subtotalPrice);
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
		$form->setTranslator($this->translator);
		$form->addSubmit('send', 'Confirm the purchase');
		$form->onSuccess[] = $this->processOrder(...);
		return $form;
	}


	/**
	 * @throws AttributeDetectionException
	 * @throws Exception
	 * @throws MoneyMismatchException
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

		$items = $this->shoppingCartSession->getItems();
		if ($items === []) {
			$form->addError('Your shopping cart is empty.');
			return;
		}

		// Pre-compute pricing and discount data before starting DB transaction
		$subtotalPrice = $this->shoppingCartSession->getSubtotalPrice();
		$discountAmount = $subtotalPrice->minus($this->shoppingCartSession->getTotalPrice());
		$discountCode = $this->discountCodeService->getCode()?->code;
		$totalPrice = $this->getTotalPrice();

		try {
			$this->orderRepository->getConnection()->begin();

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
			$customerId = $this->customerRepository->getInsertId();

			$orderData = new OrderSummary(
				customer_id: $customerId,
				carrier_id: $carrier->id,
				payment_id: $payment->id,
				carrier_price: $this->getAmountPrice($carrier->price),
				payment_price: $this->getAmountPrice($payment->price),
				subtotal_price: $this->getAmountPrice($subtotalPrice),
				total_price: $this->getAmountPrice($totalPrice),
				discount_code: $discountCode,
				discount_amount: $this->getAmountPrice($discountAmount),
				created_at: new DateTimeImmutable,
			);

			$this->orderRepository->save((array) $orderData);
			$orderId = $this->orderRepository->getInsertId();

			$this->stockReservation->reserve($items);

			foreach ($items as $item) {
				$orderProduct = OrderProduct::fromCartItem($orderId, $item);
				$this->orderProductsRepository->insert((array) $orderProduct)->execute();
			}

			if (!$this->discountCodeService->consume()) {
				throw new OrderException('The discount code has just reached its usage limit, please try again without the code.');
			}

			$this->orderRepository->getConnection()->commit();

		} catch (OrderException $e) {
			$this->rollback();
			$form->addError($this->describe($e), false);
			return;

		} catch (\Throwable $e) {
			$this->rollback();
			Debugger::log($e, Debugger::EXCEPTION);
			$form->addError('An error occurred while processing your order. Please try again.');
			return;
		}

		try {
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
		} catch (\Throwable $e) {
			Debugger::log($e, 'commerce-order-event');
		} finally {
			$this->shoppingCartSession->remove();
			$this->discountCodeService->remove();
			$this->orderSession->remove();
		}

		$this->getPresenter()->redirect($this->linkRedirectTarget);
	}


	/**
	 * Rolls the order transaction back; a failure to do so must not hide the original error.
	 */
	private function rollback(): void
	{
		try {
			$this->orderRepository->getConnection()->rollback();
		} catch (\Throwable $e) {
			Debugger::log($e, 'commerce-order-rollback');
		}
	}


	/**
	 * Builds the customer-facing message for an order that cannot be completed.
	 */
	private function describe(OrderException $e): string
	{
		return match (true) {
			$e instanceof OutOfStockException => $this->translate('The product %s is not in stock in the requested quantity.', $e->productName),
			$e instanceof ItemUnavailableException => $this->translate('The product %s is no longer available.', $e->productName),
			default => $this->translate($e->getMessage()),
		};
	}
}
