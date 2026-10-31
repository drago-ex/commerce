<?php declare(strict_types=1);

namespace Drago\Commerce\Domain\Customer;

use Dibi\Connection;
use Dibi\Row;
use Drago\Database\Table;


/** Repository for persisting customer data. */
readonly class CustomerRepository
{
	/** @var Table<Row> */
	public Table $table;


	public function __construct(Connection $db)
	{
		$this->table = new Table($db, 'customers', 'id');
	}
}
