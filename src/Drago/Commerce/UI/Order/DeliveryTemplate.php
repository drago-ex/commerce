<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Commerce\UI\Order;

use Drago\Commerce\Domain\Delivery\CarrierMapper;
use Drago\Commerce\Domain\Delivery\PaymentMapper;
use Drago\Commerce\UI\BaseTemplate;


/**
 * Template data container for delivery step.
 */
class DeliveryTemplate extends BaseTemplate
{
	/** @var CarrierMapper[] */
	public array $carrier = [];

	/** @var PaymentMapper[] */
	public array $payment = [];
}
