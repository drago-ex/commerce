<?php

declare(strict_types=1);

namespace Drago\Commerce\Event;

use Drago\Commerce\Domain\Product\Product;


/**
 * Event fired when a cart item is removed.
 */
class CartItemRemoved
{
	public function __construct(
		public Product $product,
	) {
	}
}
