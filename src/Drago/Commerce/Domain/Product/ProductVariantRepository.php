<?php

declare(strict_types=1);

namespace Drago\Commerce\Domain\Product;

use Dibi\Connection;
use Dibi\Exception;
use Drago\Attr\AttributeDetectionException;
use Drago\Attr\Table;
use Drago\Database\Database;


/**
 * Repository for reading product variants and their attribute labels, and
 * for atomically decrementing a variant's stock at checkout.
 */
#[Table(ProductVariantEntity::Table, ProductVariantEntity::PrimaryKey, entity: ProductVariantEntity::class)]
class ProductVariantRepository
{
	/** @use Database<ProductVariantEntity> */
	use Database;

	public function __construct(
		protected Connection $connection,
	) {
	}


	/**
	 * Returns a single variant by its ID, or null if not found.
	 *
	 * @throws Exception
	 * @throws AttributeDetectionException
	 */
	public function getOne(int $id): ?ProductVariantEntity
	{
		return $this->get($id)
			->record();
	}


	/**
	 * Returns all active variants of a product.
	 *
	 * @return array<ProductVariantEntity>
	 * @throws AttributeDetectionException
	 * @throws Exception
	 */
	public function getForProduct(int $productId): array
	{
		return $this->read('*')
			->where(ProductVariantEntity::ProductId, '= ?', $productId)
			->and(ProductVariantEntity::Active, '= ?', 1)
			->orderBy(ProductVariantEntity::PrimaryKey)
			->recordAll();
	}


	/**
	 * Returns the human-readable attribute labels for a variant, e.g.
	 * ["Barva: Modrá", "Velikost: M"], in a stable order (by attribute ID).
	 *
	 * @return list<string>
	 * @throws Exception
	 */
	public function getLabels(int $variantId): array
	{
		$rows = $this->connection->query('
			SELECT [a].[name] AS attribute, [v].[value] AS value
			FROM [product_variant_values] [vv]
			INNER JOIN [product_attribute_values] [v] ON [v].[id] = [vv].[attribute_value_id]
			INNER JOIN [product_attributes] [a] ON [a].[id] = [v].[attribute_id]
			WHERE [vv].[variant_id] = %i
			ORDER BY [a].[id]
		', $variantId)->fetchAll();

		return array_map(
			static fn ($row): string => $row->attribute . ': ' . $row->value,
			$rows,
		);
	}


	/**
	 * Atomically decrements a variant's stock by the given amount, but only
	 * if enough stock is currently available — same pattern as
	 * ProductRepository::decrementStock(), just scoped to a variant row
	 * instead of the product row.
	 *
	 * @throws Exception
	 */
	public function decrementStock(int $id, int $amount): bool
	{
		$this->connection->query(
			'UPDATE %n SET %n = %n - %i WHERE %n = %i AND %n >= %i',
			ProductVariantEntity::Table,
			ProductVariantEntity::Stock,
			ProductVariantEntity::Stock,
			$amount,
			ProductVariantEntity::PrimaryKey,
			$id,
			ProductVariantEntity::Stock,
			$amount,
		);

		return $this->connection->getAffectedRows() > 0;
	}
}
