<?php declare(strict_types=1);

namespace Drago\Commerce\Domain\Product;

use Dibi\Connection;
use Dibi\Exception;
use Drago\Database\Table;


/** Repository for reading predefined product attributes (e.g. "Barva", "Velikost"). */
readonly class ProductAttributeRepository
{
	/** @var Table<ProductAttributeEntity> */
	public Table $table;


	public function __construct(Connection $db)
	{
		$this->table = new Table($db, ProductAttributeEntity::Table, ProductAttributeEntity::PrimaryKey, ProductAttributeEntity::class);
	}


	/**
	 * Returns all defined attributes.
	 *
	 * @return ProductAttributeEntity[]
	 * @throws Exception
	 */
	public function getAll(): array
	{
		return $this->table->read('*')
			->orderBy(ProductAttributeEntity::Name)
			->recordAll();
	}
}
