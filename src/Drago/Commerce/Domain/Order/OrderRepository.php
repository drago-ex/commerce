<?php

declare(strict_types=1);

namespace Drago\Commerce\Domain\Order;

use Dibi\Connection;
use Dibi\Row;
use Drago\Attr\Table;
use Drago\Database\Database;


#[Table('orders', 'id')]
class OrderRepository
{
	/** @use Database<Row> */
	use Database;

	public function __construct(
		protected Connection $connection,
	) {
	}
}
