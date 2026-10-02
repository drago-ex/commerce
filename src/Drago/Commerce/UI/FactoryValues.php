<?php

declare(strict_types=1);

namespace Drago\Commerce\UI;

use Nette\Utils\ArrayHash;


/**
 * Holds values submitted by product and discount-code forms.
 */
class FactoryValues extends ArrayHash
{
	public const string
		ProductId = 'productId',
		VariantId = 'variantId',
		Amount = 'amount',
		Code = 'code';

	public int|string $productId;

	/**
	 * Holds the selected variant ID, or null when the form has no variant.
	 */
	public int|string|null $variantId = null;

	public int $amount;
	public string $code;
}
