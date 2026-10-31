<?php declare(strict_types=1);

namespace Drago\Commerce\Event;

use Drago\Commerce\Domain\Order\OrderStatus;


/**
 * Event dispatched after the state of an order has changed and was saved (e.g. to send an email
 * that the order was shipped). A cancelled order has already returned its items to stock.
 */
class OrderStatusChanged
{
	public function __construct(
		public int $orderId,
		public OrderStatus $previous,
		public OrderStatus $status,
	) {
	}
}
