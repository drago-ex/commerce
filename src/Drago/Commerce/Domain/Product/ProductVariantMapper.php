<?php

declare(strict_types=1);

namespace Drago\Commerce\Domain\Product;

use Brick\Money\Exception\UnknownCurrencyException;
use Dibi\Exception;
use Drago\Commerce\Commerce;


/**
 * Converts a ProductVariantEntity to a domain ProductVariantOption object.
 */
class ProductVariantMapper
{
	public function __construct(
		private readonly Commerce $commerce,
		private readonly ProductVariantRepository $variantRepository,
	) {
	}


	/**
	 * @param float $fallbackPrice The product's own price, used when the
	 *   variant itself has no price override.
	 *
	 * @throws UnknownCurrencyException
	 * @throws Exception
	 */
	public function map(ProductVariantEntity $entity, float $fallbackPrice): ProductVariantOption
	{
		return new ProductVariantOption(
			id: $entity->id,
			sku: $entity->sku,
			price: $this->commerce->moneyOf($entity->price ?? $fallbackPrice),
			stock: $entity->stock,
			labels: $this->variantRepository->getLabels($entity->id),
		);
	}
}
