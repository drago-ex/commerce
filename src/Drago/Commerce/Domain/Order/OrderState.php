<?php

declare(strict_types=1);

namespace Drago\Commerce\Domain\Order;

use Drago\Commerce\Domain\Customer\Customer;
use Drago\Commerce\Domain\Delivery\Carrier;
use Drago\Commerce\Domain\Delivery\Payment;


/**
 * Stores the selected carrier, payment method, and customer for an order in progress.
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
