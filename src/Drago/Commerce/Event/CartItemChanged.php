<?php

declare(strict_types=1);

namespace Drago\Commerce\Event;

use Drago\Commerce\Domain\Product\Product;


/**
 * Event fired when a cart item quantity is changed or added.
 */
class CartItemChanged
{
	public function __construct(
		public Product $product,
		public int $amount,
	) {
	}
}
