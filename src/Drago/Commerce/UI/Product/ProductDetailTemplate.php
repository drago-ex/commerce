<?php

declare(strict_types=1);

namespace Drago\Commerce\UI\Product;

use Brick\Money\Money;
use Drago\Commerce\Domain\Product\ProductEntity;
use Drago\Commerce\Domain\Product\ProductVariantOption;
use Drago\Commerce\UI\BaseTemplate;


class ProductDetailTemplate extends BaseTemplate
{
	public ProductEntity $product;

	/** @var list<string> */
	public array $images = [];

	/** @var list<ProductVariantOption> */
	public array $variants = [];

	/** @var list<array{id: int, name: string, values: list<array{id: int, value: string}>}> */
	public array $attributeGroups = [];

	public ?ProductVariantOption $selectedVariant = null;

	public string $variantMatrixJson = '[]';

	/** Price to pay for the selected variant (or the product), after discount. */
	public Money $price;

	/** Price before discount, or null when there is no discount. */
	public ?Money $originalPrice = null;

	public int $discountPercent = 0;
}
