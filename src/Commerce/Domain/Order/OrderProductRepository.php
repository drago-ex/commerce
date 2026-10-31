<?php declare(strict_types=1);

namespace Drago\Commerce\Domain\Order;

use Dibi\Connection;
use Dibi\Row;
use Drago\Database\Table;


/**
 * Repository for persisting order-product relations.
 *
 * The order line has a surrogate primary key, but this repository is
 * intentionally insert-only because the package does not edit order lines.
 */
readonly class OrderProductRepository
{
	/** @var Table<Row> */
	public Table $table;


	public function __construct(Connection $db)
	{
		$this->table = new Table($db, 'orders_products');
	}
}
