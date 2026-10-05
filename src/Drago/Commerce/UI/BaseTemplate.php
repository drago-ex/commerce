<?php

declare(strict_types=1);

namespace Drago\Commerce\UI;

use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Drago\Application\UI\ExtraTemplate;
use Drago\Commerce\Commerce;
use Drago\Commerce\Domain\Product\ProductCart;
use Latte\Attributes\TemplateFilter;
use NumberFormatter;


class BaseTemplate extends ExtraTemplate
{
	/** @var ProductCart[] */
	public array $shoppingCart;

	public int $amountItems;

	public Money $totalPrice;

	public Breadcrumbs $breadcrumbs;


	/**
	 * Formats a Money object to a localized currency string.
	 */
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

		$formatter->setAttribute(
			NumberFormatter::MIN_FRACTION_DIGITS,
			Commerce::$moneyFractionDigits,
		);
		$formatter->setAttribute(
			NumberFormatter::MAX_FRACTION_DIGITS,
			Commerce::$moneyFractionDigits,
		);

		return $money->formatWith($formatter);
	}


	/**
	 * Converts a numeric amount to a formatted currency string.
	 *
	 * @throws UnknownCurrencyException
	 */
	#[TemplateFilter]
	public function price(float|int $amount): string
	{
		$money = Money::of($amount, Commerce::$currency);
		return $this->money($money);
	}
}
