<?php

declare(strict_types=1);

namespace Drago\Commerce\Domain\Order;


/**
 * Result of a successfully persisted order.
 */
class OrderPlacement
{
	public function __construct(
		public int $orderId,
		public OrderSummary $orderSummary,
	) {
	}
}
