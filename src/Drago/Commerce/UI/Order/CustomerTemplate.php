<?php

declare(strict_types=1);

namespace Drago\Commerce\UI\Order;

use Drago\Commerce\Domain\Delivery\Carrier;
use Drago\Commerce\Domain\Delivery\Payment;
use Drago\Commerce\UI\BaseTemplate;


/**
 * Template for customer control.
 */
class CustomerTemplate extends BaseTemplate
{
	public ?Carrier $carrier = null;

	public ?Payment $payment = null;
}
