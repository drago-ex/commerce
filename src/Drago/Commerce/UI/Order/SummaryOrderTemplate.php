<?php

declare(strict_types=1);

namespace Drago\Commerce\UI\Order;

use Brick\Money\Money;
use Drago\Commerce\Domain\Customer\Customer;
use Drago\Commerce\Domain\Delivery\Carrier;
use Drago\Commerce\Domain\Delivery\Payment;
use Drago\Commerce\UI\BaseTemplate;


/**
 * Template variables for the order summary view.
 */
class SummaryOrderTemplate extends BaseTemplate
{
	public ?Customer $customer = null;
	public ?Payment $payment = null;
	public ?Carrier $carrier = null;

	/**
	 * Sum of cart item prices before the discount code is applied.
	 */
	public Money $subtotalPrice;

	/**
	 * Amount deducted by the applied discount code (zero when none is applied).
	 */
	public Money $discountAmount;

	/**
	 * Code of the currently applied discount, or null when none is applied.
	 */
	public ?string $discountCode = null;
}
