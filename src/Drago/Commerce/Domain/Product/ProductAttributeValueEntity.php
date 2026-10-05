<?php

declare(strict_types=1);

namespace Drago\Commerce\Domain\Product;

use Drago\Database\Entity;


/**
 * Represents a row in the product_attribute_values table — a predefined
 * value for an attribute (e.g. "Modrá" for attribute "Barva").
 */
class ProductAttributeValueEntity extends Entity
{
	public const string
		Table = 'product_attribute_values',
		PrimaryKey = 'id',
		AttributeId = 'attribute_id',
		Value = 'value';

	public int $id;
	public int $attribute_id;
	public string $value;
}
