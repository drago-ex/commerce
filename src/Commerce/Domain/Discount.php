<?php

declare(strict_types=1);

namespace Drago\Commerce\Domain;

use Brick\Math\RoundingMode;
use Brick\Money\Money;


/**
 * Adds discount property and related methods to a class.
 * Helps to reuse discount logic across multiple domain models.
 *
 * Expects the using class to be an {@see Item} (i.e. to have a public Money $price),
 * since the discount is always expressed relative to that item's price.
 */
trait Discount
{
	public ?int $discount = null;


	public function setDiscount(?int $discount): void
	{
		$this->discount = $discount;
	}


	public function getDiscount(): ?int
	{
		return $this->discount;
	}


	/**
	 * Whether this item currently has an active (positive) discount.
	 */
	public function hasDiscount(): bool
	{
		return $this->discount !== null && $this->discount > 0;
	}


	/**
	 * Returns the unit price after the discount percentage has been applied.
	 * Falls back to the original price when there is no discount.
	 */
	public function getDiscountedPrice(): Money
	{
		$discount = $this->discount;
		if ($discount === null || $discount <= 0) {
			return $this->price;
		}

		$ratio = max(0, min(100, $discount)) / 100;
		return $this->price->multipliedBy(1 - $ratio, RoundingMode::HALF_UP);
	}


	/**
	 * Returns the amount saved per unit thanks to the discount (original price minus discounted price).
	 */
	public function getDiscountAmount(): Money
	{
		return $this->price->minus($this->getDiscountedPrice());
	}
}
