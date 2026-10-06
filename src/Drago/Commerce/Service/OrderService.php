<?php

declare(strict_types=1);

namespace Drago\Commerce\Service;

use Brick\Money\Money;
use Brick\PhoneNumber\PhoneNumber;
use Brick\PhoneNumber\PhoneNumberFormat;
use DateTimeImmutable;
use Dibi\Connection;
use Dibi\Exception;
use Drago\Attr\AttributeDetectionException;
use Drago\Commerce\Domain\Customer\Customer;
use Drago\Commerce\Domain\Customer\CustomerRepository;
use Drago\Commerce\Domain\Delivery\Carrier;
use Drago\Commerce\Domain\Delivery\Payment;
use Drago\Commerce\Domain\DiscountCode\DiscountCodeEntity;
use Drago\Commerce\Domain\Order\OrderException;
use Drago\Commerce\Domain\Order\OrderPlacement;
use Drago\Commerce\Domain\Order\OrderProduct;
use Drago\Commerce\Domain\Order\OrderProductRepository;
use Drago\Commerce\Domain\Order\OrderRepository;
use Drago\Commerce\Domain\Order\OrderSummary;
use Drago\Commerce\Domain\Order\StockReservation;
use Drago\Commerce\Domain\Product\ProductCart;
use Throwable;
use Tracy\Debugger;


/**
 * Coordinates the transactional creation of an order.
 *
 * UI controls should only collect/validate request data and react to the result.
 * All database writes belonging to one order are committed or rolled back together.
 */
readonly class OrderService
{
	public function __construct(
		private OrderRepository $orderRepository,
		private OrderProductRepository $orderProductsRepository,
		private CustomerRepository $customerRepository,
		private StockReservation $stockReservation,
		private DiscountCodeService $discountCodeService,
	) {
	}


	/**
	 * Persists a complete order in one database transaction.
	 * $discountCode is the code the totals were calculated with; its usage is
	 * recorded in the same transaction.
	 *
	 * @param ProductCart[] $items
	 * @throws OrderException
	 * @throws Throwable
	 */
	public function place(
		Customer $customer,
		Carrier $carrier,
		Payment $payment,
		array $items,
		Money $subtotalPrice,
		Money $discountAmount,
		Money $totalPrice,
		?DiscountCodeEntity $discountCode,
	): OrderPlacement
	{
		$connection = $this->orderRepository->getConnection();
		$connection->begin();

		try {
			$customerId = $this->saveCustomer($customer);

			$orderData = new OrderSummary(
				customer_id: $customerId,
				carrier_id: $carrier->id,
				payment_id: $payment->id,
				carrier_price: $this->getAmountPrice($carrier->price),
				payment_price: $this->getAmountPrice($payment->price),
				subtotal_price: $this->getAmountPrice($subtotalPrice),
				total_price: $this->getAmountPrice($totalPrice),
				discount_code: $discountCode?->code,
				discount_amount: $this->getAmountPrice($discountAmount),
				created_at: new DateTimeImmutable,
			);

			$this->orderRepository->save((array) $orderData);
			$orderId = $this->orderRepository->getInsertId();

			$this->stockReservation->reserve($items);
			$this->saveProducts($orderId, $items);

			if ($discountCode !== null && !$this->discountCodeService->consume($discountCode)) {
				throw new OrderException('The discount code can no longer be used, please try again without the code.');
			}

			$connection->commit();
			return new OrderPlacement($orderId, $orderData);
		} catch (Throwable $e) {
			$this->rollback($connection);
			throw $e;
		}
	}


	/**
	 * @throws Exception
	 * @throws AttributeDetectionException
	 */
	private function saveCustomer(Customer $customer): int
	{
		$phone = $customer->phone instanceof PhoneNumber
			? $customer->phone->format(PhoneNumberFormat::INTERNATIONAL)
			: (string) $customer->phone;

		$customerData = new Customer(
			email: $customer->email,
			phone: $phone,
			name: $customer->name,
			surname: $customer->surname,
			street: $customer->street,
			city: $customer->city,
			postal_code: $customer->postal_code,
			country: $customer->country,
			note: $customer->note,
		);

		$this->customerRepository->save((array) $customerData);
		return $this->customerRepository->getInsertId();
	}


	/**
	 * @throws AttributeDetectionException
	 * @throws Exception
	 */
	private function saveProducts(int $orderId, array $items): void
	{
		foreach ($items as $item) {
			$orderProduct = OrderProduct::fromCartItem($orderId, $item);
			$this->orderProductsRepository->insert((array) $orderProduct)->execute();
		}
	}


	private function getAmountPrice(Money $money): float
	{
		return $money->getAmount()->toFloat();
	}


	private function rollback(Connection $connection): void
	{
		try {
			$connection->rollback();
		} catch (Throwable $e) {
			// A rollback failure must not hide the original exception.
			Debugger::log($e, 'commerce-order-rollback');
		}
	}
}
