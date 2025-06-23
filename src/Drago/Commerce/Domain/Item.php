<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Commerce\Domain;

use Brick\Money\Money;


abstract class Item
{
	public function __construct(
		public int $id,
		public string $name,
		public Money $price,
	) {
	}
}
