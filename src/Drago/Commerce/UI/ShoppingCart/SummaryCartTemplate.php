<?php

declare(strict_types=1);

namespace Drago\Commerce\UI\ShoppingCart;

use Brick\Money\Money;
use Drago\Commerce\UI\BaseTemplate;


/**
 * Template data for ShoppingCartControl
 */
class SummaryCartTemplate extends BaseTemplate
{
	public string $linkOrderDelivery;
	public ?string $discountCode = null;
	public Money $originalPrice;
	public Money $subtotalPrice;
	public Money $productDiscountAmount;
	public Money $discountAmount;
}
