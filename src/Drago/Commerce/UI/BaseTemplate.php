<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Commerce\UI;

use Brick\Money\Money;
use Drago\Application\UI\ExtraTemplate;
use Drago\Commerce\Commerce;
use Latte\Attributes\TemplateFilter;


/**
 * Base template providing common template functionality.
 */
class BaseTemplate extends ExtraTemplate
{
	public array $shoppingCart;
	public Money $totalPrice;
	public int $amountItems;


	#[TemplateFilter]
	public function money(Money $money): string
	{
		return $money->formatTo(Commerce::$moneyFormat);
	}
}
