<?php declare(strict_types=1);

namespace Drago\Commerce\Domain\Product;

use Brick\Math\Exception\MathException;
use Brick\Money\Exception\UnknownCurrencyException;
use Dibi\Exception;
use Drago\Commerce\Commerce;


/** Converts a ProductVariantEntity to a domain ProductVariantOption object. */
readonly class ProductVariantMapper
{
	public function __construct(
		private Commerce $commerce,
		private ProductVariantRepository $variantRepository,
	) {
	}


	/**
	 * @param float $fallbackPrice The product's own price, used when the
	 *   variant itself has no price override.
	 *
	 * @throws UnknownCurrencyException
	 * @throws Exception
	 * @throws MathException
	 */
	public function map(ProductVariantEntity $entity, float $fallbackPrice): ProductVariantOption
	{
		return $this->build(
			$entity,
			$fallbackPrice,
			$this->variantRepository->getLabels($entity->id),
			$this->variantRepository->getAttributeValueIds($entity->id),
		);
	}


	/**
	 * Maps all given variants of one product using a single query for their
	 * attributes, instead of two queries per variant as map() does.
	 *
	 * @param ProductVariantEntity[] $entities Variants of the product $productId.
	 * @param float $fallbackPrice The product's own price.
	 * @return list<ProductVariantOption>
	 *
	 * @throws UnknownCurrencyException
	 * @throws Exception
	 * @throws MathException
	 */
	public function mapMany(int $productId, array $entities, float $fallbackPrice): array
	{
		$attributes = $this->variantRepository->getAttributesForProduct($productId);

		$options = [];
		foreach ($entities as $entity) {
			$rows = $attributes[$entity->id] ?? [];
			$options[] = $this->build(
				$entity,
				$fallbackPrice,
				array_map(static fn(array $row): string => $row['attribute'] . ': ' . $row['value'], $rows),
				array_map(static fn(array $row): int => $row['valueId'], $rows),
			);
		}

		return $options;
	}


	/**
	 * @param list<string> $labels
	 * @param list<int> $attributeValueIds
	 * @throws UnknownCurrencyException
	 * @throws MathException
	 */
	private function build(
		ProductVariantEntity $entity,
		float $fallbackPrice,
		array $labels,
		array $attributeValueIds,
	): ProductVariantOption
	{
		return new ProductVariantOption(
			id: $entity->id,
			sku: $entity->sku,
			price: $this->commerce->moneyOf($entity->price ?? $fallbackPrice),
			stock: $entity->stock,
			labels: $labels,
			attributeValueIds: $attributeValueIds,
			priceOverridden: $entity->hasPriceOverride(),
		);
	}
}
