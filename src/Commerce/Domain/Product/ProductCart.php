<?php

declare(strict_types=1);

namespace Drago\Commerce\Domain\Product;

use Brick\Math\BigInteger;
use Brick\Money\Money;


/**
 * Represents an item in the cart.
 */
class ProductCart
{
	public function __construct(
		public Product $product,
		public BigInteger $amount,
		public ?int $variantId = null,
		public ?string $variantLabel = null,
	) {
	}


	public function getUnitPrice(): Money
	{
		return $this->product->getDiscountedPrice();
	}
}
