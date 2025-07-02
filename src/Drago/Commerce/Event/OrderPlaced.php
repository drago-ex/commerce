<?php

declare(strict_types=1);

namespace Drago\Commerce\Event;

use Drago\Commerce\Domain\Customer\Customer;
use Drago\Commerce\Domain\Delivery\Carrier;
use Drago\Commerce\Domain\Delivery\Payment;
use Drago\Commerce\Service\ShoppingCartSession;
use Drago\Commerce\UI\Order\OrderSummary;


/**
 * Event dispatched after a successful order placement.
 */
class OrderPlaced
{
	public function __construct(
		public OrderSummary $orderSummary,
		public Customer $customer,
		public Carrier $carrier,
		public Payment $payment,
		public ShoppingCartSession $shoppingCartSession,
	) {
	}
}
