<?php

declare(strict_types=1);

namespace Drago\Commerce\Event;

use Drago\Commerce\Domain\Customer\Customer;
use Drago\Commerce\Domain\Delivery\Carrier;
use Drago\Commerce\Domain\Delivery\Payment;
use Drago\Commerce\Domain\Order\OrderSummary;
use Drago\Commerce\Domain\Product\ProductCart;
use Drago\Commerce\Service\ShoppingCartSession;


/**
 * Event dispatched after a successful order placement.
 *
 * The order is already saved and stock is already taken, so a failing listener cannot
 * undo it; the error is logged. The cart session is emptied right after the listeners
 * have run, so a listener that defers its work (a queue, a later job) must use $items,
 * a snapshot of the ordered lines, and not $shoppingCartSession.
 */
class OrderPlaced
{
	/**
	 * @param ProductCart[] $items The ordered lines, with variants.
	 */
	public function __construct(
		public int $orderId,
		public OrderSummary $orderSummary,
		public Customer $customer,
		public Carrier $carrier,
		public Payment $payment,
		public ShoppingCartSession $shoppingCartSession,
		public array $items = [],
		public string $lang = 'en',
	) {
	}
}
