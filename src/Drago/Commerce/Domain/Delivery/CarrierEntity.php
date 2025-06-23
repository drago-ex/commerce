<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Commerce\Domain\Delivery;

use Drago\Database\Entity;


/**
 * Entity representation of a Carrier in the database.
 */
class CarrierEntity extends Entity
{
	public const string
		Table = 'carrier',
		PrimaryKey = 'id';

	public int $id;
	public string $name;
	public float $price;
}
