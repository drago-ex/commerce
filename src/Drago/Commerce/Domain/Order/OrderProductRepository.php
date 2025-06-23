<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Commerce\Domain\Order;

use Dibi\Connection;
use Drago\Attr\Table;
use Drago\Database\Database;


#[Table('orders_products', 'id')]
class OrderProductRepository
{
	use Database;

	public function __construct(
		protected Connection $connection,
	) {
	}
}
