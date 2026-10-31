<?php declare(strict_types=1);

namespace Drago\Commerce\Domain\Order;

use Dibi\Connection;
use Dibi\Exception;
use Dibi\Row;
use Drago\Database\Table;


/**
 * Repository for persisting order-product relations.
 *
 * The order line has a surrogate primary key, but this repository does not edit order lines:
 * they are inserted with the order and read when it is cancelled.
 */
readonly class OrderProductRepository
{
	/** @var Table<Row> */
	public Table $table;


	public function __construct(Connection $db)
	{
		$this->table = new Table($db, 'orders_products');
	}


	/**
	 * The lines of an order: product_id, variant_id and amount.
	 * @return list<Row>
	 * @throws Exception
	 */
	public function getByOrder(int $orderId): array
	{
		return $this->table->read('%n, %n, %n', 'product_id', 'variant_id', 'amount')
			->where('%n = ?', 'order_id', $orderId)
			->recordAll();
	}
}
