<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

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
	/**
	 * Items in the shopping cart
	 */
	public array $shoppingCart;

	/**
	 * Total number of items
	 */
	public int $amountItems;

	/**
	 * Total price as a Money object
	 */
	public Money $totalPrice;

	/**
	 * Breadcrumbs navigation data
	 */
	public Breadcrumbs $breadcrumbs;


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

		if (Commerce::$moneyFractionDigits !== null) {
			$formatter->setAttribute(
				NumberFormatter::MIN_FRACTION_DIGITS,
				Commerce::$moneyFractionDigits,
			);
			$formatter->setAttribute(
				NumberFormatter::MAX_FRACTION_DIGITS,
				Commerce::$moneyFractionDigits,
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
