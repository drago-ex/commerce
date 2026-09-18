<?php

declare(strict_types=1);

namespace Drago\Commerce\Domain\Product;

use Drago\Database\Entity;


/**
 * Represents a row in the product_variants table — one concrete sellable
 * combination of attribute values for a product (e.g. "Blue" + "M"), with
 * its own stock and an optional price override (null = inherits the
 * product's own price).
 */
class ProductVariantEntity extends Entity
{
	public const string
		Table = 'product_variants',
		PrimaryKey = 'id',
		ProductId = 'product_id',
		Sku = 'sku',
		Price = 'price',
		Stock = 'stock',
		Active = 'active';

	public int $id;
	public int $product_id;
	public ?string $sku;
	public ?float $price;
	public int $stock;
	public int $active;
}
