<?php

declare(strict_types=1);

namespace Drago\Commerce\Domain\Product;

use Dibi\Connection;
use Dibi\Exception;
use Drago\Attr\AttributeDetectionException;
use Drago\Attr\Table;
use Drago\Database\Database;


/**
 * Repository for reading predefined product attributes (e.g. "Barva", "Velikost").
 */
#[Table(ProductAttributeEntity::Table, ProductAttributeEntity::PrimaryKey, entity: ProductAttributeEntity::class)]
class ProductAttributeRepository
{
	/** @use Database<ProductAttributeEntity> */
	use Database;

	public function __construct(
		protected Connection $connection,
	) {
	}


	/**
	 * Returns all defined attributes.
	 *
	 * @return array<ProductAttributeEntity>
	 * @throws AttributeDetectionException
	 * @throws Exception
	 */
	public function getAll(): array
	{
		return $this->read('*')
			->orderBy(ProductAttributeEntity::Name)
			->recordAll();
	}
}
