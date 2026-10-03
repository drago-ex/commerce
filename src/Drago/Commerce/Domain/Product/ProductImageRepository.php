<?php

declare(strict_types=1);

namespace Drago\Commerce\Domain\Product;

use Dibi\Connection;
use Dibi\Exception;
use Dibi\Row;
use Drago\Attr\AttributeDetectionException;
use Drago\Attr\Table;
use Drago\Database\Database;


#[Table('product_images')]
class ProductImageRepository
{
	/** @use Database<Row> */
	use Database;

	public function __construct(
		protected Connection $connection,
	) {
	}


	/**
	 * Returns additional product images in their display order.
	 *
	 * @return list<string>
	 * @throws Exception
	 * @throws AttributeDetectionException
	 */
	public function getForProduct(int $productId): array
	{
		$rows = $this->read('image')
			->where('product_id = ?', $productId)
			->orderBy('position', 'id')
			->fetchAll();

		$images = [];
		foreach ($rows as $row) {
			$images[] = (string) $row['image'];
		}

		return $images;
	}
}
