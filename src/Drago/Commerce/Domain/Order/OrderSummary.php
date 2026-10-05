<?php

declare(strict_types=1);

namespace Drago\Commerce\Domain\Order;

use DateTimeImmutable;


/**
 * Represents a persisted customer order summary.
 */
class OrderSummary
{
	public function __construct(
		public int $customer_id,
		public int $carrier_id,
		public int $payment_id,
		public float $carrier_price,
		public float $payment_price,
		public float $subtotal_price,
		public float $total_price,
		public ?string $discount_code,
		public float $discount_amount,
		public DateTimeImmutable $created_at,
	) {
	}
}
