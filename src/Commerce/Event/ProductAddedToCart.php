<?php

declare(strict_types=1);

namespace Drago\Commerce\Event;

use Brick\Money\Money;
use Drago\Commerce\Domain\Product\Product;


/**
 * Event triggered when a product is added to the cart, before it is stored.
 *
 * $product->price (and the initial $finalPrice) is the price the item is sold at before
 * any listener runs: the variant's own price when it has one, otherwise the product price.
 * It does not include the product's percentage discount. That discount is applied
 * automatically afterwards, but only when no listener has changed the price and the
 * variant does not have its own price.
 */
class ProductAddedToCart
{
	public function __construct(
		public Product $product,
		public Money $finalPrice,
		public ?int $variantId = null,
		public ?string $variantLabel = null,
		public int $amount = 1,
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
