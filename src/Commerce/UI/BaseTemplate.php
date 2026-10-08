<?php

declare(strict_types=1);

namespace Drago\Commerce\UI;

use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Drago\Application\UI\ExtraTemplate;
use Drago\Commerce\Commerce;
use Drago\Commerce\Domain\Product\ProductCart;
use Latte\Attributes\TemplateFilter;


class BaseTemplate extends ExtraTemplate
{
	/** @var ProductCart[] */
	public array $shoppingCart;

	public int $amountItems;

	public Money $totalPrice;

	public Breadcrumbs $breadcrumbs;

	/** Full cart price before product discounts, when available. */
	public ?Money $originalPrice = null;

	/** Cart subtotal after product discounts, before discount codes. */
	public Money $subtotalPrice;

	/** Savings from product discounts. */
	public Money $productDiscountAmount;

	/** Savings from the applied discount code. */
	public Money $discountAmount;

	/** Applied discount code, or null when none is active. */
	public ?string $discountCode = null;


	/**
	 * Formats a Money object to a localized currency string.
	 */
	#[TemplateFilter]
	public function money(Money $money): string
	{
		return Commerce::formatMoney($money);
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
