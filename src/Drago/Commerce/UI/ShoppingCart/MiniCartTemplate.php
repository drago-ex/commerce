<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Commerce\UI\ShoppingCart;

use Drago\Commerce\UI\BaseTemplate;


/**
 * Template variables for BasketControl.
 */
class MiniCartTemplate extends BaseTemplate
{
	public string $linkShoppingCart;
	public string $formattedTotalPrice;
}
