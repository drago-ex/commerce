<?php

declare(strict_types=1);

namespace Drago\Commerce\Domain\Product;

use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Drago\Commerce\Commerce;


/**
 * Stock and price overview of a product's active variants, used in listings
 * where the individual variants are not shown.
 */
final class ProductVariantSummary
{
	public int $count = 0;
	public int $stock = 0;

	/** Whether any variant inherits the product price (and so its discount). */
	public bool $inheritsPrice = false;

	/** @var list<float> Own prices of the variants that have one. */
	private array $ownPrices = [];


	public function add(int $stock, ?float $ownPrice): void
	{
		$this->count++;
		$this->stock += $stock;

		if ($ownPrice === null) {
			$this->inheritsPrice = true;
		} else {
			$this->ownPrices[] = $ownPrice;
		}
	}


	public function inStock(): bool
	{
		return $this->stock > 0;
	}


	/**
	 * Returns the lowest price a customer can pay for any variant: its own
	 * price, or the product's price after the product discount.
	 *
	 * @throws UnknownCurrencyException
	 */
	public function getLowestPrice(ProductEntity $product, Commerce $commerce): Money
	{
		$lowest = $this->inheritsPrice || $this->ownPrices === []
			? $product->getDiscountedPrice()
			: null;

		foreach ($this->ownPrices as $price) {
			$money = $commerce->moneyOf($price);
			if ($lowest === null || $money->isLessThan($lowest)) {
				$lowest = $money;
			}
		}

		return $lowest ?? $product->getDiscountedPrice();
	}
}
