<?php

declare(strict_types=1);

namespace Drago\Commerce\Event;

use Drago\Commerce\Domain\Product\Product;


/**
 * Event fired when the quantity of a line already in the cart is changed.
 * Adding to the cart fires ProductAddedToCart instead.
 *
 * $amount is the new quantity of the line, not the difference.
 */
class CartItemChanged
{
	public function __construct(
		public Product $product,
		public int $amount,
		public ?int $variantId = null,
		public ?string $variantLabel = null,
	) {
	}
}
