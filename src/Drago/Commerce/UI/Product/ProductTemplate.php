<?php

declare(strict_types=1);

namespace Drago\Commerce\UI\Product;

use Drago\Commerce\Domain\Product\ProductEntity;
use Drago\Commerce\UI\BaseTemplate;


/**
 * Template variables for the product listing control.
 */
class ProductTemplate extends BaseTemplate
{
	/** @var ProductEntity[] */
	public array $products = [];
}
