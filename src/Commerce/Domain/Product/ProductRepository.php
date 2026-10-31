<?php declare(strict_types=1);

namespace Drago\Commerce\Domain\Product;

use Dibi\Connection;
use Dibi\Exception;
use Dibi\Fluent;
use Drago\Database\Table;


/** Repository for reading product data from the database. */
class ProductRepository
{
	/** @var Table<ProductEntity> */
	public Table $table;


	public function __construct(Connection $db)
	{
		$this->table = new Table($db, ProductEntity::Table, ProductEntity::PrimaryKey, ProductEntity::class);
	}


	/**
	 * Returns a single product entity by its ID, or null if not found.
	 *
	 * @throws Exception
	 */
	public function getOne(int $id): ?ProductEntity
	{
		return $this->table->get($id)
			->record();
	}


	/**
	 * Returns the number of active products.
	 *
	 * @throws Exception
	 */
	public function countActive(): int
	{
		return $this->table->count([ProductEntity::Active => true]);
	}


	/**
	 * Returns one page of active products in a stable order.
	 *
	 * @return ProductEntity[]
	 * @throws Exception
	 */
	public function getPage(int $limit, int $offset): array
	{
		$query = $this->table->read('*')
			->where(ProductEntity::Active, '= ?', 1)
			->orderBy(ProductEntity::PrimaryKey);

		$query->limit($limit);
		$query->offset($offset);
		return $query->recordAll();
	}


	/**
	 * Returns all active products.
	 *
	 * @return ProductEntity[]
	 * @throws Exception
	 */
	public function getAll(): array
	{
		return $this->table->read('*')
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
		return $this->table->command()
			->update(ProductEntity::Table)
			->set('%n = %n - %i', ProductEntity::Stock, ProductEntity::Stock, $amount)
			->where('%n = ?', ProductEntity::PrimaryKey, $id)
			->where('%n >= %i', ProductEntity::Stock, $amount)
			->execute(Fluent::AffectedRows) > 0;
	}


	/**
	 * Returns the amount to stock, for an item of a cancelled order.
	 * @throws Exception
	 */
	public function incrementStock(int $id, int $amount): void
	{
		$this->table->command()
			->update(ProductEntity::Table)
			->set('%n = %n + %i', ProductEntity::Stock, ProductEntity::Stock, $amount)
			->where('%n = ?', ProductEntity::PrimaryKey, $id)
			->execute();
	}
}
