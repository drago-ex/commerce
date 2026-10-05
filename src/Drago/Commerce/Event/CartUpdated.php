<?php

declare(strict_types=1);

namespace Drago\Commerce\Event;

use Drago\Commerce\Domain\Product\Product;


/**
 * Event fired when the cart is updated.
 *
 * @deprecated Never dispatched. Use ProductAddedToCart, CartItemChanged or CartItemRemoved.
 */
class CartUpdated
{
	public function __construct(
		public Product $product,
	) {
	}
}
