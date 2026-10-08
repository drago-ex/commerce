<?php

declare(strict_types=1);

namespace Drago\Commerce\Domain\Product;

use Drago\Database\Entity;


/**
 * Represents a row in the product_attributes table (e.g. "Barva", "Velikost").
 */
class ProductAttributeEntity extends Entity
{
	public const string
		Table = 'product_attributes',
		PrimaryKey = 'id',
		Name = 'name';

	public int $id;
	public string $name;
}
