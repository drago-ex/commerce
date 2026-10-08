<?php

declare(strict_types=1);

namespace Drago\Commerce\Domain\Delivery;

use Brick\Money\Exception\UnknownCurrencyException;
use Dibi\Connection;
use Dibi\Exception;
use Drago\Attr\AttributeDetectionException;
use Drago\Attr\Table;
use Drago\Database\Database;


/**
 * Repository for reading carrier data from the database.
 */
#[Table(CarrierEntity::Table, CarrierEntity::PrimaryKey, entity: CarrierEntity::class)]
class CarrierRepository
{
	/** @use Database<CarrierEntity> */
	use Database;

	public function __construct(
		protected Connection $connection,
		private readonly CarrierMapper $carrier,
	) {
	}


	/**
	 * Returns a map of carrier IDs to themselves.
	 *
	 * @return array<int|string, int|string>
	 * @throws AttributeDetectionException
	 */
	public function getOnlyIds(): array
	{
		return $this->read('*')
			->fetchPairs(CarrierEntity::PrimaryKey, CarrierEntity::PrimaryKey);
	}


	/**
	 * Returns a single carrier entity by its ID, or null if not found.
	 *
	 * @throws AttributeDetectionException
	 * @throws Exception
	 */
	public function getOne(int $id): ?CarrierEntity
	{
		return $this->get($id)
			->record();
	}


	/**
	 * Returns all carrier entities.
	 *
	 * @return list<CarrierEntity>
	 * @throws AttributeDetectionException
	 * @throws Exception
	 */
	public function getAll(): array
	{
		return $this->read('*')
			->recordAll();
	}


	/**
	 * Returns all carriers mapped to domain Carrier objects.
	 *
	 * @return list<Carrier>
	 * @throws AttributeDetectionException
	 * @throws Exception
	 * @throws UnknownCurrencyException
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
