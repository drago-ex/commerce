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
 * Repository for reading payment method data from the database.
 */
#[Table(PaymentEntity::Table, PaymentEntity::PrimaryKey, class: PaymentEntity::class)]
class PaymentRepository
{
	/** @use Database<PaymentEntity> */
	use Database;

	public function __construct(
		protected Connection $connection,
		private readonly PaymentMapper $payment,
	) {
	}


	/**
	 * Returns a map of payment IDs to themselves.
	 *
	 * @return array<int|string, int|string>
	 * @throws AttributeDetectionException
	 */
	public function getOnlyIds(): array
	{
		return $this->read('*')
			->fetchPairs(PaymentEntity::PrimaryKey, PaymentEntity::PrimaryKey);
	}


	/**
	 * Returns a single payment entity by its ID, or null if not found.
	 *
	 * @throws AttributeDetectionException
	 * @throws Exception
	 */
	public function getOne(int $id): ?PaymentEntity
	{
		return $this->get($id)
			->record();
	}


	/**
	 * Returns all payment entities.
	 *
	 * @return list<PaymentEntity>
	 * @throws AttributeDetectionException
	 * @throws Exception
	 */
	private function getAll(): array
	{
		return $this->read('*')
			->recordAll();
	}


	/**
	 * Returns all payment methods mapped to domain Payment objects.
	 *
	 * @return list<Payment>
	 * @throws AttributeDetectionException
	 * @throws Exception
	 * @throws UnknownCurrencyException
	 */
	public function getPaymentItems(): array
	{
		$data = [];
		foreach ($this->getAll() as $entity) {
			$data[] = $this->payment->map($entity);
		}

		return $data;
	}
}
