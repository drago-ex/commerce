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


	/**
	 * Atomically decrements stock by the given amount, but only if enough
	 * stock is currently available. The availability check and the
	 * decrement happen in a single SQL statement, so concurrent orders
	 * for the same product cannot both succeed when only one has
	 * sufficient stock ("overselling").
	 *
	 * Returns true when the decrement succeeded, false when there was
	 * not enough stock (e.g. it was just taken by another concurrent
	 * order) — in that case, no row was modified.
	 *
	 * @throws Exception
	 */
	public function decrementStock(int $id, int $amount): bool
	{
		$this->connection->query(
			'UPDATE %n SET %n = %n - %i WHERE %n = %i AND %n >= %i',
			ProductEntity::Table,
			ProductEntity::Stock,
			ProductEntity::Stock,
			$amount,
			ProductEntity::PrimaryKey,
			$id,
			ProductEntity::Stock,
			$amount,
		);

		return $this->connection->getAffectedRows() > 0;
	}
}
