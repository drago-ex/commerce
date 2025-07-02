<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Commerce\Domain\Product;

use App\Commerce\Domain\Discount;
use Drago\Commerce\Domain\Item;


/**
 * Basic product information.
 */
class Product extends Item
{
	use Discount;
}
