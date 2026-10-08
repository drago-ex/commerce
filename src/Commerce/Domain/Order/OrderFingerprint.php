<?php

declare(strict_types=1);

namespace Drago\Commerce\Domain\Order;

use Brick\Money\Money;
use Brick\PhoneNumber\PhoneNumber;
use Brick\PhoneNumber\PhoneNumberFormat;
use Drago\Commerce\Domain\Customer\Customer;
use Drago\Commerce\Domain\Delivery\Carrier;
use Drago\Commerce\Domain\Delivery\Payment;
use Drago\Commerce\Domain\DiscountCode\DiscountCodeEntity;
use Drago\Commerce\Domain\Product\ProductCart;
use JsonException;


/**
 * Creates a stable fingerprint of the order details shown at checkout.
 */
final class OrderFingerprint
{
	/**
	 * @param ProductCart[] $items
	 * @throws JsonException
	 */
	public static function create(
		array $items,
		OrderState $order,
		?DiscountCodeEntity $discountCode,
		Money $subtotalPrice,
		Money $discountAmount,
		Money $totalPrice,
	): string
	{
		$cart = [];
		foreach ($items as $item) {
			$cart[] = [
				'productId' => $item->product->id,
				'name' => $item->product->name,
				'variantId' => $item->variantId,
				'variantLabel' => $item->variantLabel,
				'amount' => (string) $item->amount,
				'price' => self::moneyData($item->product->price),
				'discount' => $item->product->discount,
				'unitPrice' => self::moneyData($item->getUnitPrice()),
			];
		}

		usort(
			$cart,
			static fn(array $a, array $b): int => [
				$a['productId'],
				$a['variantId'] ?? 0,
				$a['name'],
			] <=> [
				$b['productId'],
				$b['variantId'] ?? 0,
				$b['name'],
			],
		);

		return hash('sha256', json_encode([
			'items' => $cart,
			'carrier' => self::carrierData($order->carrier),
			'payment' => self::paymentData($order->payment),
			'customer' => self::customerData($order->customer),
			'discountCode' => self::discountCodeData($discountCode),
			'subtotalPrice' => self::moneyData($subtotalPrice),
			'discountAmount' => self::moneyData($discountAmount),
			'totalPrice' => self::moneyData($totalPrice),
		], JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION));
	}


	/**
	 * @return array{id: int, name: string, price: array{amount: string, currency: string}}|null
	 */
	private static function carrierData(?Carrier $carrier): ?array
	{
		return $carrier === null ? null : [
			'id' => $carrier->id,
			'name' => $carrier->name,
			'price' => self::moneyData($carrier->price),
		];
	}


	/**
	 * @return array{id: int, name: string, price: array{amount: string, currency: string}}|null
	 */
	private static function paymentData(?Payment $payment): ?array
	{
		return $payment === null ? null : [
			'id' => $payment->id,
			'name' => $payment->name,
			'price' => self::moneyData($payment->price),
		];
	}


	/**
	 * @return array<string, mixed>|null
	 */
	private static function customerData(?Customer $customer): ?array
	{
		if ($customer === null) {
			return null;
		}

		return [
			'email' => $customer->email,
			'phone' => $customer->phone instanceof PhoneNumber
				? $customer->phone->format(PhoneNumberFormat::INTERNATIONAL)
				: $customer->phone,
			'name' => $customer->name,
			'surname' => $customer->surname,
			'street' => $customer->street,
			'city' => $customer->city,
			'postalCode' => $customer->postal_code,
			'country' => $customer->country,
			'note' => $customer->note,
		];
	}


	/**
	 * @return array<string, mixed>|null
	 */
	private static function discountCodeData(?DiscountCodeEntity $discountCode): ?array
	{
		return $discountCode === null ? null : [
			'id' => $discountCode->id,
			'code' => $discountCode->code,
			'type' => $discountCode->type,
			'value' => $discountCode->value,
			'minimumOrderAmount' => $discountCode->minimum_order_amount,
		];
	}


	/**
	 * @return array{amount: string, currency: string}
	 */
	private static function moneyData(Money $money): array
	{
		return [
			'amount' => (string) $money->getAmount(),
			'currency' => $money->getCurrency()->getCurrencyCode(),
		];
	}
}
