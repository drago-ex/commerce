<?php

declare(strict_types=1);

namespace Drago\Commerce\Domain\Product;

use Dibi\Connection;
use Dibi\Exception;


class ProductImageRepository
{
	public function __construct(
		private readonly Connection $connection,
	) {
	}


	/**
	 * Returns additional product images in their display order.
	 *
	 * @return list<string>
	 * @throws Exception
	 */
	public function getForProduct(int $productId): array
	{
		$rows = $this->connection->query(
			'SELECT [image] FROM [product_images] WHERE [product_id] = %i ORDER BY [position], [id]',
			$productId,
		)->fetchAll();

		$images = [];
		foreach ($rows as $row) {
			$images[] = (string) $row['image'];
		}

		return $images;
	}
}
