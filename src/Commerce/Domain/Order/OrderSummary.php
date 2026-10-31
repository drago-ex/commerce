<?php declare(strict_types=1);

namespace Drago\Commerce\Domain\Order;

use DateTimeImmutable;


/** Represents a persisted customer order summary; the amounts are exact decimal strings ("150.00") for the DECIMAL columns. */
class OrderSummary
{
	public function __construct(
		public int $customerId,
		public int $carrierId,
		public int $paymentId,
		public string $carrierPrice,
		public string $paymentPrice,
		public string $subtotalPrice,
		public string $totalPrice,
		public ?string $discountCode,
		public string $discountAmount,
		public DateTimeImmutable $createdAt,
		public string $currency = '',
		public string $carrierName = '',
		public string $paymentName = '',
	) {
	}
}
