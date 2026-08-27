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
 * Note: the `orders_products` table has a composite primary key
 * (order_id, product_id) — no surrogate `id` column exists, so no
 * primary key is declared here. This repository is insert-only;
 * do not add get()/find()-by-id style methods without first adding
 * a real primary key column, or the generated `WHERE id = ?` query
 * will fail.
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
