<?php

declare(strict_types=1);

namespace Drago\Commerce\Domain\Product;

use Brick\Money\Money;


/**
 * One sellable variant of a product, ready for display or selection.
 */
final class ProductVariantOption
{
	/**
	 * @param list<string> $labels e.g. ["Barva: Modrá", "Velikost: M"]
	 */
	public function __construct(
		public int $id,
		public ?string $sku,
		public ?Money $price,
		public int $stock,
		public array $labels,
	) {
	}


	/**
	 * Combined, human-readable label, e.g. "Barva: Modrá, Velikost: M".
	 */
	public function getLabel(): string
	{
		return implode(', ', $this->labels);
	}


	public function inStock(): bool
	{
		return $this->stock > 0;
	}
}
