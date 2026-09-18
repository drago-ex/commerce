<?php

declare(strict_types=1);

namespace Drago\Commerce\Domain\Product;

use Dibi\Connection;
use Dibi\Exception;
use Drago\Attr\AttributeDetectionException;
use Drago\Attr\Table;
use Drago\Database\Database;


/**
 * Repository for reading predefined attribute values (e.g. "Modrá", "M").
 */
#[Table(ProductAttributeValueEntity::Table, ProductAttributeValueEntity::PrimaryKey, entity: ProductAttributeValueEntity::class)]
class ProductAttributeValueRepository
{
	/** @use Database<ProductAttributeValueEntity> */
	use Database;

	public function __construct(
		protected Connection $connection,
	) {
	}


	/**
	 * Returns all predefined values for a given attribute.
	 *
	 * @return array<ProductAttributeValueEntity>
	 * @throws AttributeDetectionException
	 * @throws Exception
	 */
	public function getByAttribute(int $attributeId): array
	{
		return $this->read('*')
			->where(ProductAttributeValueEntity::AttributeId, '= ?', $attributeId)
			->orderBy(ProductAttributeValueEntity::Value)
			->recordAll();
	}
}
