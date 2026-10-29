<?php declare(strict_types=1);

namespace Drago\Commerce\Domain\Order;

use DateTimeImmutable;


/**
 * Represents a persisted customer order summary.
 */
class OrderSummary
{
	public function __construct(
		public int $customerId,
		public int $carrierId,
		public int $paymentId,
		public float $carrierPrice,
		public float $paymentPrice,
		public float $subtotalPrice,
		public float $totalPrice,
		public ?string $discountCode,
		public float $discountAmount,
		public DateTimeImmutable $createdAt,
		public string $currency = '',
		public string $carrierName = '',
		public string $paymentName = '',
	) {
	}
}
