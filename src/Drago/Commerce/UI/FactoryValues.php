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
		VariantId = 'variantId',
		Amount = 'amount',
		Code = 'code';

	public int $productId;

	/**
	 * Set only when a variant-selecting form included the hidden variantId
	 * field (see Factory::addHiddenProductId()) — stays at this default
	 * otherwise, so products without variants are unaffected.
	 */
	public ?int $variantId = null;

	public int $amount;
	public string $code;
}
