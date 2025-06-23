<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Commerce\Domain\Delivery;

use Drago\Database\Entity;


/**
 * Entity class representing a payment method.
 */
class PaymentEntity extends Entity
{
	public const string
		Table = 'payment',
		PrimaryKey = 'id';

	public int $id;
	public string $name;
	public float $price;
}
