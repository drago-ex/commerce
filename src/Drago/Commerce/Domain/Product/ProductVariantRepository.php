<?php

declare(strict_types=1);

namespace Drago\Commerce\Domain\Product;

use Dibi\Connection;
use Dibi\Exception;
use Dibi\Fluent;
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
	 * @return ProductVariantEntity[]
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
	 * @throws AttributeDetectionException
	 */
	public function getLabels(int $variantId): array
	{
		$rows = $this->command()
			->select('a.name AS attribute')
			->select('v.value AS value')
			->from('product_variant_values vv')
			->innerJoin('product_attribute_values v')
			->on('v.id = vv.attribute_value_id')
			->innerJoin('product_attributes a')
			->on('a.id = v.attribute_id')
			->where('vv.variant_id = ?', $variantId)
			->orderBy('a.id')
			->fetchAll();

		$labels = [];
		foreach ($rows as $row) {
			$labels[] = $row['attribute'] . ': ' . $row['value'];
		}

		return $labels;
	}


	/**
	 * Returns the attribute value IDs for a variant, in stable order (by attribute ID).
	 *
	 * @return list<int>
	 * @throws Exception
	 * @throws AttributeDetectionException
	 */
	public function getAttributeValueIds(int $variantId): array
	{
		$rows = $this->command()
			->select('vv.attribute_value_id')
			->from('product_variant_values vv')
			->innerJoin('product_attribute_values v')
			->on('v.id = vv.attribute_value_id')
			->innerJoin('product_attributes a')
			->on('a.id = v.attribute_id')
			->where('vv.variant_id = ?', $variantId)
			->orderBy('a.id')
			->fetchAll();

		$ids = [];
		foreach ($rows as $row) {
			$ids[] = (int) $row['attribute_value_id'];
		}

		return $ids;
	}


	/**
	 * Returns all unique attribute groups and their values available for a given product.
	 *
	 * @return list<array{id: int, name: string, values: list<array{id: int, value: string}>}>
	 * @throws Exception
	 * @throws AttributeDetectionException
	 */
	public function getProductAttributeGroups(int $productId): array
	{
		$rows = $this->command()
			->select('DISTINCT a.id AS attribute_id')
			->select('a.name AS attribute_name')
			->select('v.id AS value_id')
			->select('v.value AS value_name')
			->from('product_variants pv')
			->innerJoin('product_variant_values vv')
			->on('vv.variant_id = pv.id')
			->innerJoin('product_attribute_values v')
			->on('v.id = vv.attribute_value_id')
			->innerJoin('product_attributes a')
			->on('a.id = v.attribute_id')
			->where('pv.product_id = ?', $productId)
			->where('pv.active = ?', 1)
			->orderBy('a.id')
			->orderBy('v.id')
			->fetchAll();

		$groups = [];
		foreach ($rows as $row) {
			$attrId = (int) $row['attribute_id'];
			if (!isset($groups[$attrId])) {
				$groups[$attrId] = [
					'id' => $attrId,
					'name' => (string) $row['attribute_name'],
					'values' => [],
				];
			}
			$groups[$attrId]['values'][] = [
				'id' => (int) $row['value_id'],
				'value' => (string) $row['value_name'],
			];
		}

		return array_values($groups);
	}


	/**
	 * Atomically decrements a variant's stock by the given amount, but only
	 * if enough stock is currently available — same pattern as
	 * ProductRepository::decrementStock(), just scoped to a variant row
	 * instead of the product row.
	 *
	 * @throws Exception
	 * @throws AttributeDetectionException
	 */
	public function decrementStock(int $id, int $amount): bool
	{
		return $this->command()
			->update(ProductVariantEntity::Table)
			->set('%n = %n - %i', ProductVariantEntity::Stock, ProductVariantEntity::Stock, $amount)
			->where('%n = ?', ProductVariantEntity::PrimaryKey, $id)
			->where('%n >= %i', ProductVariantEntity::Stock, $amount)
			->execute(Fluent::AffectedRows) > 0;
	}
}
