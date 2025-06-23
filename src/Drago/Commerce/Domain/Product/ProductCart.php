<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Commerce\Domain\Product;

use Brick\Math\BigInteger;


/**
 * Represents an item in the cart.
 */
class ProductCart
{
	public function __construct(
		public Product $product,
		public BigInteger $amount,
	) {
	}
}
