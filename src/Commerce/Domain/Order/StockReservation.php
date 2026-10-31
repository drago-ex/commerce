<?php declare(strict_types=1);

namespace Drago\Commerce\Domain\Order;

use Dibi\Exception;
use Dibi\Row;
use Drago\Commerce\Domain\Product\ProductCart;
use Drago\Commerce\Domain\Product\ProductRepository;
use Drago\Commerce\Domain\Product\ProductVariantRepository;


/**
 * Validates cart items against the database and takes their quantity out of
 * stock. A product that has active variants can only be ordered through a
 * variant, and its stock is tracked on the variant, not on the product.
 *
 * Must run inside the caller's transaction: each stock decrement is atomic,
 * but the reservation as a whole is only undone by rolling that transaction back.
 * release() returns the stock of a cancelled order.
 */
readonly class StockReservation
{
	public function __construct(
		private ProductRepository $productRepository,
		private ProductVariantRepository $variantRepository,
	) {
	}


	/**
	 * @param ProductCart[] $items
	 * @throws OrderException An item is unavailable or out of stock.
	 * @throws Exception
	 */
	public function reserve(array $items): void
	{
		// Always lock rows in the same order, so two concurrent orders
		// containing the same items cannot deadlock each other.
		usort($items, static fn(ProductCart $a, ProductCart $b): int => [$a->product->id, $a->variantId ?? 0]
			<=> [$b->product->id, $b->variantId ?? 0]);

		foreach ($items as $item) {
			$name = $item->product->name;
			$amount = $item->amount->toInt();
			if ($amount < 1) {
				throw new ItemUnavailableException($name);
			}

			$product = $this->productRepository->getOne($item->product->id);
			if ($product === null || !$product->active) {
				throw new ItemUnavailableException($name);
			}

			if ($item->variantId === null) {
				if ($this->variantRepository->hasActive($product->id)) {
					throw new ItemUnavailableException($name);
				}

				if (!$this->productRepository->decrementStock($product->id, $amount)) {
					throw new OutOfStockException($name);
				}

				continue;
			}

			$variant = $this->variantRepository->getOne($item->variantId);
			if ($variant === null || $variant->productId !== $product->id || !$variant->active) {
				throw new ItemUnavailableException($name);
			}

			if (!$this->variantRepository->decrementStock($variant->id, $amount)) {
				throw new OutOfStockException($name);
			}
		}
	}


	/**
	 * Returns the ordered amounts to stock, for a cancelled order: to the variant when the line has one,
	 * otherwise to the product, as reserve() took them.
	 * @param list<Row> $lines rows with product_id, variant_id and amount (OrderProductRepository::getByOrder())
	 * @throws Exception
	 */
	public function release(array $lines): void
	{
		// the same order of the rows as reserve(), so a cancellation and an order cannot deadlock
		usort($lines, static fn(Row $a, Row $b): int => [$a['product_id'], $a['variant_id'] ?? 0]
			<=> [$b['product_id'], $b['variant_id'] ?? 0]);

		foreach ($lines as $line) {
			if ($line['variant_id'] !== null) {
				$this->variantRepository->incrementStock((int) $line['variant_id'], (int) $line['amount']);
			} else {
				$this->productRepository->incrementStock((int) $line['product_id'], (int) $line['amount']);
			}
		}
	}
}
