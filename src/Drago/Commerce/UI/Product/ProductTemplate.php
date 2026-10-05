<?php

declare(strict_types=1);

namespace Drago\Commerce\UI\Product;

use Brick\Money\Money;
use Drago\Commerce\Domain\Product\ProductEntity;
use Drago\Commerce\Domain\Product\ProductVariantSummary;
use Drago\Commerce\UI\BaseTemplate;


/**
 * Template data for the product listing.
 */
class ProductTemplate extends BaseTemplate
{
	/** @var ProductEntity[] */
	public array $products = [];

	/**
	 * Active-variant summary of the products that have variants, keyed by product ID.
	 *
	 * @var array<int, ProductVariantSummary>
	 */
	public array $variantSummaries = [];

	/**
	 * Lowest price a customer can pay for products that have variants, keyed by product ID.
	 *
	 * @var array<int, Money>
	 */
	public array $lowestPrices = [];
}
