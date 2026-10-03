<?php

declare(strict_types=1);

namespace Drago\Commerce\UI\Product;

use Drago\Commerce\Domain\Product\ProductEntity;
use Drago\Commerce\Domain\Product\ProductVariantOption;
use Drago\Commerce\UI\BaseTemplate;


class ProductDetailTemplate extends BaseTemplate
{
	public ProductEntity $product;

	/** @var list<ProductVariantOption> */
	public array $variants = [];

	/** @var list<array{id: int, name: string, values: list<array{id: int, value: string}>}> */
	public array $attributeGroups = [];

	public ?ProductVariantOption $selectedVariant = null;

	public string $variantMatrixJson = '[]';
}
