<?php

declare(strict_types=1);

namespace Drago\Commerce\UI\Order;

use Drago\Commerce\Domain\Product\ProductCart;


/**
 * Represents products associated with an order.
 */
class OrderProduct
{
	public function __construct(
		public int $order_id,
		public int $product_id,
		public ?int $variant_id,
		public int $amount,
		public float $unit_price,
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
		);
	}
}
