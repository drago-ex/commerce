<?php

declare(strict_types=1);

namespace Drago\Commerce\Event;

use Drago\Commerce\Domain\Product\Product;


/**
 * Event fired when a line is removed from the cart.
 */
class CartItemRemoved
{
	public function __construct(
		public Product $product,
		public ?int $variantId = null,
		public ?string $variantLabel = null,
	) {
	}
}
