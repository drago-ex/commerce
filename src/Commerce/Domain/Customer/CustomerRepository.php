<?php

declare(strict_types=1);

namespace Drago\Commerce\Domain\Customer;

use Dibi\Connection;
use Dibi\Row;
use Drago\Attr\Table;
use Drago\Database\Database;


/**
 * Repository for persisting customer data.
 */
#[Table('customers', 'id')]
class CustomerRepository
{
	/** @use Database<Row> */
	use Database;

	public function __construct(
		protected Connection $connection,
	) {
	}
}
