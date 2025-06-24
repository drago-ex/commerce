<?php

declare(strict_types=1);

namespace Drago\Commerce\UI;

use Brick\Money\Exception\UnknownCurrencyException;
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


	/**
	 * @throws UnknownCurrencyException
	 */
	#[TemplateFilter]
	public function price(float|int $amount): string
	{
		$money = Money::of($amount, Commerce::$currency);
		return $this->money($money);
	}
}
