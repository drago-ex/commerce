<?php

declare(strict_types=1);

namespace Drago\Commerce\Domain\Product;

use Drago\Commerce\Domain\Discount;
use Drago\Commerce\Domain\Item;


/**
 * Basic product information.
 */
class Product extends Item
{
	use Discount;

	public ?string $photo = null;

	/** Catalog price the item was added to the cart at (null when unknown). */
	public ?CatalogPrice $catalog = null;
}
