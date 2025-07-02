<?php

declare(strict_types=1);

namespace Drago\Commerce\Event;

use Drago\Commerce\Domain\Product\Product;


/**
 * Event fired when the cart is updated.
 * (Use this event for general cart updates not covered by item changes.)
 */
class CartUpdated
{
	public function __construct(
		public Product $product,
	) {
	}
}
