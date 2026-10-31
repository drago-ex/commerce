<?php declare(strict_types=1);

namespace Drago\Commerce\Domain\Delivery;

use Brick\Math\Exception\MathException;
use Brick\Money\Exception\UnknownCurrencyException;
use Dibi\Connection;
use Dibi\Exception;
use Drago\Database\Table;


/** Repository for reading payment method data from the database. */
class PaymentRepository
{
	/** @var Table<PaymentEntity> */
	public Table $table;


	public function __construct(
		Connection $db,
		private readonly PaymentMapper $payment,
	) {
		$this->table = new Table($db, PaymentEntity::Table, PaymentEntity::PrimaryKey, PaymentEntity::class);
	}


	/**
	 * Returns a map of payment IDs to themselves.
	 *
	 * @return array<int|string, int|string>
	 */
	public function getOnlyIds(): array
	{
		return $this->table->read('*')
			->fetchPairs(PaymentEntity::PrimaryKey, PaymentEntity::PrimaryKey);
	}


	/**
	 * Returns a single payment entity by its ID, or null if not found.
	 *
	 * @throws Exception
	 */
	public function getOne(int $id): ?PaymentEntity
	{
		return $this->table->get($id)
			->record();
	}


	/**
	 * Returns all payment methods mapped to domain Payment objects.
	 *
	 * @return list<Payment>
	 * @throws Exception
	 * @throws UnknownCurrencyException
	 * @throws MathException
	 */
	public function getPaymentItems(): array
	{
		$data = [];
		foreach ($this->getAll() as $entity) {
			$data[] = $this->payment->map($entity);
		}

		return $data;
	}


	/**
	 * Returns all payment entities.
	 *
	 * @return list<PaymentEntity>
	 * @throws Exception
	 */
	private function getAll(): array
	{
		return $this->table->read('*')
			->recordAll();
	}
}
