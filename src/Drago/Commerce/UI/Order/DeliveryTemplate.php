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
	/** @temp CarrierMapper[] */
	public array $carrier = [];

	/** @temp PaymentMapper[] */
	public array $payment = [];
}
