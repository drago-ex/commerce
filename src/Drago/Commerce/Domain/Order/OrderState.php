<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Commerce\Domain\Order;

use Drago\Commerce\Domain\Customer\Customer;
use Drago\Commerce\Domain\Delivery\Carrier;
use Drago\Commerce\Domain\Delivery\Payment;


/**
 * Represents all components of an order stored in the session.
 * This class aggregates the carrier, payment, and customer information
 * related to a single order. Each property can be null if not set yet.
 */
class OrderState
{
	public function __construct(
		public ?Carrier $carrier,
		public ?Payment $payment,
		public ?Customer $customer,
	) {
	}
}
