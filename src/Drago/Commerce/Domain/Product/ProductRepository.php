<?php

declare(strict_types=1);

namespace Drago\Commerce\Domain\Product;

use Dibi\Connection;
use Dibi\Exception;
use Drago\Attr\AttributeDetectionException;
use Drago\Attr\Table;
use Drago\Database\Database;


/**
 * Repository for reading product data from the database.
 */
#[Table(ProductEntity::Table, ProductEntity::PrimaryKey, class: ProductEntity::class)]
class ProductRepository
{
	/** @use Database<ProductEntity> */
	use Database;

	public function __construct(
		protected Connection $connection,
	) {
	}


	/**
	 * Returns a single product entity by its ID, or null if not found.
	 *
	 * @throws Exception
	 * @throws AttributeDetectionException
	 */
	public function getOne(int $id): ?ProductEntity
	{
		return $this->get($id)
			->record();
	}


	/**
	 * Returns all active products.
	 *
	 * @return array<ProductEntity>
	 * @throws AttributeDetectionException
	 * @throws Exception
	 */
	public function getAll(): array
	{
		return $this->read('*')
			->where(ProductEntity::Active, '= ?', 1)
			->recordAll();
	}
}
