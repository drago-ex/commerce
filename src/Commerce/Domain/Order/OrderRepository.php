<?php declare(strict_types=1);

namespace Drago\Commerce\Domain\Order;

use Dibi\Connection;
use Dibi\Row;
use Drago\Database\Table;


/** Repository for persisting order records. */
readonly class OrderRepository
{
	/** @var Table<Row> */
	public Table $table;


	public function __construct(Connection $db)
	{
		$this->table = new Table($db, 'orders', 'id');
	}
}
