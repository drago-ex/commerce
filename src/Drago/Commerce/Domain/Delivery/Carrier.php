<?php

declare(strict_types=1);

namespace Drago\Commerce\Domain\Delivery;

use Drago\Commerce\Domain\Discount;
use Drago\Commerce\Domain\Item;


/**
 * Represents a shipping carrier with an optional discount.
 */
class Carrier extends Item
{
	use Discount;
}
