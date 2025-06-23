<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Commerce\Domain\Delivery;

use Brick\Money\Exception\UnknownCurrencyException;
use Dibi\Connection;
use Dibi\Exception;
use Drago\Attr\AttributeDetectionException;
use Drago\Attr\Table;
use Drago\Database\Database;


#[Table(CarrierEntity::Table, CarrierEntity::PrimaryKey)]
class CarrierRepository
{
	use Database;

	public function __construct(
		protected Connection $connection,
		private readonly CarrierMapper $carrier,
	) {
	}


	/**
	 * @throws AttributeDetectionException
	 */
	public function getOnlyIds(): array
	{
		return $this->read('*')
			->fetchPairs(CarrierEntity::PrimaryKey, CarrierEntity::PrimaryKey);
	}


	/**
	 * @throws AttributeDetectionException
	 * @throws Exception
	 */
	public function getOne(int $id): array|CarrierEntity|null
	{
		return $this->get($id)->execute()
			->setRowClass(CarrierEntity::class)
			->fetch();
	}


	/**
	 * @return CarrierEntity[]
	 * @throws AttributeDetectionException
	 * @throws Exception
	 */
	public function getAll(): array
	{
		return $this->read('*')->execute()
			->setRowClass(CarrierEntity::class)
			->fetchAll();
	}


	/**
	 * @return CarrierMapper[]
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
