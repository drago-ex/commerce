<?php declare(strict_types=1);

namespace Drago\Commerce\Domain\Order;

use Drago\Commerce\Domain\Product\ProductCart;


/** Represents a product line stored in an order; the unit price is an exact decimal string. */
class OrderProduct
{
	public function __construct(
		public int $orderId,
		public int $productId,
		public ?int $variantId,
		public int $amount,
		public string $unitPrice,
		public string $productName = '',
		public ?string $variantLabel = null,
	) {
	}


	public static function fromCartItem(int $orderId, ProductCart $item): self
	{
		return new self(
			orderId: $orderId,
			productId: $item->product->id,
			variantId: $item->variantId,
			amount: $item->amount->toInt(),
			unitPrice: (string) $item->getUnitPrice()->getAmount(),
			productName: mb_substr($item->product->name, 0, 100),
			variantLabel: $item->variantLabel !== null ? mb_substr($item->variantLabel, 0, 255) : null,
		);
	}
}
