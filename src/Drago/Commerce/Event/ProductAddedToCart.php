<?php

declare(strict_types=1);

namespace Drago\Commerce\Event;

use Brick\Money\Money;
use Drago\Commerce\Domain\Product\Product;


/**
 * Event triggered when a product is added to the cart.
 */
class ProductAddedToCart
{
	public function __construct(
		public Product $product,
		public Money $finalPrice,
	) {
	}


	/**
	 * Set the final price after discounts or modifications.
	 */
	public function setPrice(Money $price): void
	{
		$this->finalPrice = $price;
	}


	/**
	 * Get the current final price of the product.
	 */
	public function getPrice(): Money
	{
		return $this->finalPrice;
	}
}
