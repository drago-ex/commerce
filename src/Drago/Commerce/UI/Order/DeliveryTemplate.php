<?php

declare(strict_types=1);

namespace Drago\Commerce\UI\Order;

use Drago\Commerce\Domain\Delivery\Carrier;
use Drago\Commerce\Domain\Delivery\Payment;
use Drago\Commerce\UI\BaseTemplate;


/**
 * Template data container for delivery step.
 */
class DeliveryTemplate extends BaseTemplate
{
	/** @var Carrier[] */
	public array $carrier = [];

	/** @var Payment[] */
	public array $payment = [];
}
