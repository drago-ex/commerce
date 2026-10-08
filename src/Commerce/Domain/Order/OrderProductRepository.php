<?php

declare(strict_types=1);

namespace Drago\Commerce\Domain\Order;

use Dibi\Connection;
use Dibi\Row;
use Drago\Attr\Table;
use Drago\Database\Database;


/**
 * Repository for persisting order-product relations.
 *
 * The order line has a surrogate primary key, but this repository is
 * intentionally insert-only because the package does not edit order lines.
 */
#[Table('orders_products')]
class OrderProductRepository
{
	/** @use Database<Row> */
	use Database;

	public function __construct(
		protected Connection $connection,
	) {
	}
}
