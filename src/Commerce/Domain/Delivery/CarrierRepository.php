<?php declare(strict_types=1);

namespace Drago\Commerce\Domain\Delivery;

use Brick\Math\Exception\MathException;
use Brick\Money\Exception\UnknownCurrencyException;
use Dibi\Connection;
use Dibi\Exception;
use Drago\Database\Table;


/** Repository for reading carrier data from the database. */
class CarrierRepository
{
	/** @var Table<CarrierEntity> */
	public Table $table;


	public function __construct(
		Connection $db,
		private readonly CarrierMapper $carrier,
	) {
		$this->table = new Table($db, CarrierEntity::Table, CarrierEntity::PrimaryKey, CarrierEntity::class);
	}


	/**
	 * Returns a map of carrier IDs to themselves.
	 *
	 * @return array<int|string, int|string>
	 */
	public function getOnlyIds(): array
	{
		return $this->table->read('*')
			->fetchPairs(CarrierEntity::PrimaryKey, CarrierEntity::PrimaryKey);
	}


	/**
	 * Returns a single carrier entity by its ID, or null if not found.
	 *
	 * @throws Exception
	 */
	public function getOne(int $id): ?CarrierEntity
	{
		return $this->table->get($id)
			->record();
	}


	/**
	 * Returns all carrier entities.
	 *
	 * @return list<CarrierEntity>
	 * @throws Exception
	 */
	public function getAll(): array
	{
		return $this->table->read('*')
			->recordAll();
	}


	/**
	 * Returns all carriers mapped to domain Carrier objects.
	 *
	 * @return list<Carrier>
	 * @throws Exception
	 * @throws UnknownCurrencyException
	 * @throws MathException
	 */
	public function getCarrierItems(): array
	{
		$data = [];
		foreach ($this->getAll() as $entity) {
			$data[] = $this->carrier->map($entity);
		}

		return $data;
	}
}
