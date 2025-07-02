<?php

declare(strict_types=1);

namespace Drago\Commerce\Event;

use Drago\Commerce\Domain\Delivery\Carrier;
use Drago\Commerce\Domain\Delivery\Payment;


/**
 * Event fired when delivery options (carrier/payment) are selected or updated.
 */
class DeliveryOptionsChanged
{
	public function __construct(
		public Carrier $carrier,
		public Payment $payment,
	) {}
}
