<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Commerce\UI;

use Nette\Utils\ArrayHash;


/**
 * Represents form data for product operations.
 */
class FactoryData extends ArrayHash
{
	public const string
		ProductId = 'productId',
		Amount = 'amount';

	public int $productId;
	public int $amount;
}
