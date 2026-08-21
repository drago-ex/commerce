<?php

declare(strict_types=1);

namespace Drago\Commerce\UI;

use Nette\Utils\ArrayHash;


/**
 * Represents form data for product operations.
 */
class FactoryValues extends ArrayHash
{
	public const string
		ProductId = 'productId',
		Amount = 'amount';

	public int $productId;
	public int $amount;
}
