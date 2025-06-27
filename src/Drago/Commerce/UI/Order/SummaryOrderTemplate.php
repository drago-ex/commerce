<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Commerce\UI\Order;

use Drago\Commerce\Domain\Customer\Customer;
use Drago\Commerce\Domain\Delivery\Carrier;
use Drago\Commerce\Domain\Delivery\Payment;
use Drago\Commerce\UI\BaseTemplate;


/**
 * Template variables for the order summary view.
 */
class SummaryOrderTemplate extends BaseTemplate
{
	public Customer $customer;
	public Payment $payment;
	public Carrier $carrier;
}
