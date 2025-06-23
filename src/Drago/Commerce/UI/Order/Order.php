<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Commerce\UI\Order;

use DateTimeImmutable;


/**
 * Represents a customer order.
 */
class Order
{
	public function __construct(
		public int $customer_id,
		public int $carrier_id,
		public int $payment_id,
		public float $carrier_price,
		public float $payment_price,
		public float $total_price,
		public DateTimeImmutable $date,
	) {
	}
}
