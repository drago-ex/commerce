<?php

declare(strict_types=1);

namespace Drago\Commerce\Domain\Product;

use Brick\Money\Money;


/**
 * The catalog price of a product (or variant) at a point in time, before any
 * ProductAddedToCart listener runs. A cart line remembers it, so checkout can
 * tell that the catalog has changed since the item was added.
 */
final readonly class CatalogPrice
{
	public function __construct(
		public Money $price,
		public ?int $discount = null,
	) {
	}


	public function equals(self $other): bool
	{
		return $this->price->isEqualTo($other->price)
			&& ($this->discount ?? 0) === ($other->discount ?? 0);
	}
}
