<?php declare(strict_types=1);

namespace Drago\Commerce\Domain\Order;

use Dibi\Connection;
use Dibi\Exception;
use Dibi\Fluent;
use Drago\Database\Table;


/** Repository for persisting order records. */
readonly class OrderRepository
{
	/** @var Table<OrderEntity> */
	public Table $table;


	public function __construct(Connection $db)
	{
		$this->table = new Table($db, OrderEntity::Table, OrderEntity::PrimaryKey, OrderEntity::class);
	}


	/**
	 * The order locked for the rest of the transaction, so two changes of its state cannot cross.
	 * @throws Exception
	 */
	public function getForUpdate(int $id): ?OrderEntity
	{
		$order = $this->table->getConnection()
			->query('SELECT * FROM %n WHERE %n = %i FOR UPDATE', OrderEntity::Table, OrderEntity::PrimaryKey, $id)
			->setRowClass(OrderEntity::class)
			->fetch();

		return $order instanceof OrderEntity ? $order : null;
	}


	/** @throws Exception */
	public function setStatus(int $id, OrderStatus $status): void
	{
		$this->table->update([OrderEntity::ColumnStatus => $status])
			->where('%n = ?', OrderEntity::PrimaryKey, $id)
			->execute();
	}


	/**
	 * Records the payment of the order; returns false when it was already paid or does not exist.
	 * @throws Exception
	 */
	public function markPaid(int $id): bool
	{
		return $this->table->update([OrderEntity::ColumnPaidAt => new \DateTimeImmutable])
			->where('%n = ?', OrderEntity::PrimaryKey, $id)
			->where('%n IS NULL', OrderEntity::ColumnPaidAt)
			->execute(Fluent::AffectedRows) > 0;
	}
}
