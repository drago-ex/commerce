<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Commerce\Domain\Product;

use Brick\Money\Exception\UnknownCurrencyException;
use Drago\Commerce\Commerce;


class ProductMapper
{
	public function __construct(
		public Commerce $commerce,
	) {
	}


	/**
	 * @throws UnknownCurrencyException
	 */
	public function map(ProductEntity $entity): Product
	{
		$product = new Product(
			id: $entity->id,
			name: $entity->name,
			price: $this->commerce->moneyOf($entity->price),
		);

		$product->setDiscount($entity->discount);
		return $product;
	}
}
