<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Commerce\Domain\Delivery;

use App\Commerce\Domain\Discount;
use Drago\Commerce\Domain\Item;


/**
 * Order carrier method.
 */
class Carrier extends Item
{
	use Discount;
}
