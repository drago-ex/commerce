<?php declare(strict_types=1);

namespace Drago\Commerce\Domain\Product;

use Dibi\Connection;
use Dibi\Exception;
use Drago\Database\Table;


/** Repository for reading predefined attribute values (e.g. "Modrá", "M"). */
readonly class ProductAttributeValueRepository
{
	/** @var Table<ProductAttributeValueEntity> */
	public Table $table;


	public function __construct(Connection $db)
	{
		$this->table = new Table(
			$db,
			ProductAttributeValueEntity::Table,
			ProductAttributeValueEntity::PrimaryKey,
			ProductAttributeValueEntity::class,
		);
	}


	/**
	 * Returns all predefined values for a given attribute.
	 *
	 * @return ProductAttributeValueEntity[]
	 * @throws Exception
	 */
	public function getByAttribute(int $attributeId): array
	{
		return $this->table->read('*')
			->where(ProductAttributeValueEntity::AttributeId, '= ?', $attributeId)
			->orderBy(ProductAttributeValueEntity::Value)
			->recordAll();
	}
}
