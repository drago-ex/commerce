<?php

declare(strict_types=1);

namespace Drago\Commerce\UI\Order;

use Brick\Money\Money;
use Drago\Commerce\Domain\Delivery\Carrier;
use Drago\Commerce\Domain\Delivery\Payment;
use Drago\Commerce\UI\BaseTemplate;


/**
 * Template data container for delivery step.
 */
class DeliveryTemplate extends BaseTemplate
{
	public ?Carrier $selectedCarrier = null;

	public ?Payment $selectedPayment = null;

	public Money $originalPrice;

	public Money $productDiscountAmount;

	public Money $subtotalPrice;

	public Money $discountAmount;

	public ?string $discountCode = null;

	/** @var Carrier[] */
	public array $carrier = [];

	/** @var Payment[] */
	public array $payment = [];
}
