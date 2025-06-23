<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Commerce\UI\Order;

use Brick\Money\Exception\MoneyMismatchException;
use Brick\Money\Money;
use Brick\PhoneNumber\PhoneNumberFormat;
use DateTimeImmutable;
use Dibi\DriverException;
use Dibi\Exception;
use Drago\Application\UI\ExtraControl;
use Drago\Attr\AttributeDetectionException;
use Drago\Commerce\Domain\Customer\Customer;
use Drago\Commerce\Domain\Customer\CustomerRepository;
use Drago\Commerce\Domain\Order\OrderProductRepository;
use Drago\Commerce\Domain\Order\OrderRepository;
use Drago\Commerce\Service\OrderSession;
use Drago\Commerce\Service\ShoppingCartSession;
use Tracy\Debugger;


/**
 * @property-read SummaryTemplate $template
 */
class SummaryControl extends ExtraControl
{
	/** URL to process the order done */
	public string $linkRedirectTarget;


	public function __construct(
		private readonly ShoppingCartSession $shoppingCartSession,
		private readonly OrderSession $orderSession,
		private readonly OrderRepository $orderRepository,
		private readonly OrderProductRepository $orderProductsRepository,
		private readonly CustomerRepository $customerRepository,
	) {
	}


	public function setLinkRedirectTarget(string $link): void
	{
		if (empty($link)) {
			throw new \InvalidArgumentException('Redirect target link cannot be empty.');
		}
		$this->linkRedirectTarget = $link;
	}


	/**
	 * @throws MoneyMismatchException
	 */
	public function render(): void
	{
		$template = $this->template;
		$template->setFile(__DIR__ . '/Summary.latte');
		$template->shoppingCart = $this->shoppingCartSession->getItems();
		$template->amountItems = $this->shoppingCartSession->getAmountItems();
		$template->totalPrice = $this->getTotalPrice();
		$template->orderDraft = $this->orderSession->getItems();
		$template->render();
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


	/**
	 * @throws MoneyMismatchException
	 * @throws DriverException
	 */
	public function handleOrderDone(): void
	{
		$order = $this->orderSession->getItems();
		$customer = $order->customer;

		try {
			$this->orderRepository->getConnection()->begin();

			$customerData = new Customer(
				email: $customer->email,
				phone: $customer->phone->format(PhoneNumberFormat::INTERNATIONAL),
				name: $customer->name,
				surname: $customer->surname,
				street: $customer->street,
				city: $customer->city,
				post_code: $customer->post_code,
				country: $customer->country,
				note: $customer->note,
			);
			$this->customerRepository->save((array) $customerData);

			$orderData = new Order(
				customer_id: $this->customerRepository->getInsertId(),
				carrier_id: $order->carrier->id,
				payment_id: $order->payment->id,
				carrier_price: $this->getAmountPrice($order->carrier->price),
				payment_price: $this->getAmountPrice($order->payment->price),
				total_price: $this->getAmountPrice($this->getTotalPrice()),
				date: new DateTimeImmutable,
			);

			$this->orderRepository->save((array) $orderData);

			foreach ($this->shoppingCartSession->getItems() as $item) {
				$orderProduct = new OrderProduct(
					order_id: $this->orderRepository->getInsertId(),
					product_id: $item->product->id,
					amount: $item->amount->toInt(),
				);
				$this->orderProductsRepository->save((array) $orderProduct);
			}

			$this->orderRepository->getConnection()->commit();

			$this->shoppingCartSession->remove();
			$this->orderSession->remove();
			$this->getPresenter()->redirect($this->linkRedirectTarget);

		} catch (Exception | AttributeDetectionException $e) {
			$this->orderRepository->getConnection()->rollback();
			Debugger::barDump($e);
		}
	}
}
