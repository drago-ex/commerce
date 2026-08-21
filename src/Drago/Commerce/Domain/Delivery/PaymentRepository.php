<?php

declare(strict_types=1);

namespace Drago\Commerce\Domain\Delivery;

use Brick\Money\Exception\UnknownCurrencyException;
use Dibi\Connection;
use Dibi\Exception;
use Drago\Attr\AttributeDetectionException;
use Drago\Attr\Table;
use Drago\Database\Database;


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
	 * @return array<int|string, int|string>
	 * @throws AttributeDetectionException
	 */
	public function getOnlyIds(): array
	{
		return $this->read('*')
			->fetchPairs(PaymentEntity::PrimaryKey, PaymentEntity::PrimaryKey);
	}


	/**
	 * @throws AttributeDetectionException
	 * @throws Exception
	 */
	public function getOne(int $id): ?PaymentEntity
	{
		return $this->get($id)
			->record();
	}


	/**
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
