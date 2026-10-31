<?php declare(strict_types=1);

namespace Drago\Commerce\Domain\Product;

use Dibi\Connection;
use Dibi\Exception;
use Dibi\Row;
use Drago\Database\Table;


readonly class ProductImageRepository
{
	/** @var Table<Row> */
	public Table $table;


	public function __construct(Connection $db)
	{
		$this->table = new Table($db, 'product_images');
	}


	/**
	 * Returns additional product images in their display order.
	 *
	 * @return list<string>
	 * @throws Exception
	 */
	public function getForProduct(int $productId): array
	{
		$rows = $this->table->read('image')
			->where('product_id = ?', $productId)
			->orderBy('position')
			->orderBy('id')
			->fetchAll();

		$images = [];
		foreach ($rows as $row) {
			$images[] = (string) $row['image'];
		}

		return $images;
	}
}
