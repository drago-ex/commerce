<?php

declare(strict_types=1);

namespace Drago\Commerce\UI;

use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Drago\Application\UI\ExtraTemplate;
use Drago\Commerce\Commerce;
use Latte\Attributes\TemplateFilter;
use NumberFormatter;


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
		$formatter = new NumberFormatter(
			Commerce::$moneyFormat,
			NumberFormatter::CURRENCY,
		);

		if (Commerce::$moneySymbol) {
			$formatter->setSymbol(
				NumberFormatter::CURRENCY_SYMBOL,
				Commerce::$moneySymbol,
			);
		}

		return $money->formatWith($formatter);
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
