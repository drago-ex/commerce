<?php

declare(strict_types=1);

namespace Drago\Commerce\Domain\Delivery;

use Drago\Database\Entity;


/**
 * Entity class representing a shipping carrier stored in the database.
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
