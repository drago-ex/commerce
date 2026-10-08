<?php

declare(strict_types=1);

namespace Drago\Commerce\Domain\Order;

use Drago\Commerce\Domain\Product\ProductCart;


/**
 * Represents a product line stored in an order.
 */
class OrderProduct
{
	public function __construct(
		public int $order_id,
		public int $product_id,
		public ?int $variant_id,
		public int $amount,
		public float $unit_price,
		public string $product_name = '',
		public ?string $variant_label = null,
	) {
	}


	public static function fromCartItem(int $orderId, ProductCart $item): self
	{
		return new self(
			order_id: $orderId,
			product_id: $item->product->id,
			variant_id: $item->variantId,
			amount: $item->amount->toInt(),
			unit_price: $item->getUnitPrice()->getAmount()->toFloat(),
			product_name: mb_substr($item->product->name, 0, 100),
			variant_label: $item->variantLabel !== null ? mb_substr($item->variantLabel, 0, 255) : null,
		);
	}
}
