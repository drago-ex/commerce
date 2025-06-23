<?php

declare(strict_types=1);

namespace Drago\Commerce\Service;

use Brick\Money\Exception\MoneyMismatchException;
use Brick\Money\Money;
use Drago\Commerce\Commerce;
use Drago\Commerce\Domain\Customer\Customer;
use Drago\Commerce\Domain\Delivery\Carrier;
use Drago\Commerce\Domain\Delivery\Payment;
use Drago\Commerce\Domain\Order\OrderDraft;
use Nette\Http\Session;
use Nette\Http\SessionSection;


/**
 * Class responsible for managing a customer order.
 * Stores carrier, payment, and customer information in session.
 */
class OrderSession
{
	private const string
		Carrier = 'carrier',
		Payment = 'payment',
		Customer = 'customer';

	private SessionSection $sessionSection;


	public function __construct(
		private readonly Session $session,
		private readonly Commerce $commerce,
	) {
		$this->sessionSection = $this->session->getSection(self::class);
	}


	/**
	 * Returns all keys used in the order (carrier, payment, customer).
	 *
	 * @return string[] List of order item keys.
	 */
	private function items(): array
	{
		return [
			self::Carrier,
			self::Payment,
			self::Customer,
		];
	}


	/**
	 * Loads all order items from session.
	 *
	 * @return OrderDraft<Carrier|null, Payment|null, Customer|null>
	 */
	public function getItems(): OrderDraft
	{
		$items = [];
		foreach ($this->items() as $item) {
			$items[$item] = $this->sessionSection->get($item);
		}

		return new OrderDraft(
			$items[self::Carrier] ?? null,
			$items[self::Payment] ?? null,
			$items[self::Customer] ?? null,
		);
	}


	/**
	 * Saves carrier information into session.
	 *
	 * @param Carrier $carrier Carrier object.
	 */
	public function addItemCarrier(Carrier $carrier): void
	{
		$this->sessionSection->set(self::Carrier, $carrier);
	}


	/**
	 * Calculates carrier price.
	 *
	 * @throws MoneyMismatchException When currency types mismatch.
	 * @return Money Price for carrier.
	 */
	public function getCarrierPrice(): Money
	{
		$carrierPrice = $this->commerce->moneyZero();
		$carrier = $this->getItems()->carrier;

		if ($carrier !== null && isset($carrier->price)) {
			$carrierPrice = $carrierPrice->plus($carrier->price);
		}

		return $carrierPrice;
	}


	/**
	 * Saves payment information into session.
	 *
	 * @param Payment $payment Payment object.
	 */
	public function addItemPayment(Payment $payment): void
	{
		$this->sessionSection->set(self::Payment, $payment);
	}


	/**
	 * Calculates payment price.
	 *
	 * @throws MoneyMismatchException When currency types mismatch.
	 * @return Money Price for payment.
	 */
	public function getPaymentPrice(): Money
	{
		$paymentPrice = $this->commerce->moneyZero();
		$payment = $this->getItems()->payment;

		if ($payment !== null && isset($payment->price)) {
			$paymentPrice = $paymentPrice->plus($payment->price);
		}

		return $paymentPrice;
	}


	/**
	 * Saves customer information into session.
	 *
	 * @param Customer $customer Customer object.
	 */
	public function addItemCustomer(Customer $customer): void
	{
		$this->sessionSection->set(self::Customer, $customer);
	}


	/**
	 * Removes all order items from session.
	 */
	public function remove(): void
	{
		foreach ($this->items() as $item) {
			$this->sessionSection->remove($item);
		}
	}
}
