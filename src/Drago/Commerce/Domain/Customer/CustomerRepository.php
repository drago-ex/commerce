<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Commerce\Domain\Customer;

use Dibi\Connection;
use Drago\Attr\Table;
use Drago\Database\Database;


#[Table('customers', 'id')]
class CustomerRepository
{
	use Database;

	public function __construct(
		protected Connection $connection,
	) {
	}
}
