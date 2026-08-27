<?php

declare(strict_types=1);

namespace Drago\Commerce\UI\Order;


/**
 * Represents products associated with an order.
 */
class OrderProduct
{
	public function __construct(
		public int $order_id,
		public int $product_id,
		public int $amount,
		public float $unit_price,
	) {
	}
}
