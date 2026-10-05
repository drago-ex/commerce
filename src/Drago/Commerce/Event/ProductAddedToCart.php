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


	public function setPrice(Money $price): void
	{
		$this->finalPrice = $price;
	}


	public function getPrice(): Money
	{
		return $this->finalPrice;
	}
}
