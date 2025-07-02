<?php

declare(strict_types=1);

namespace Drago\Commerce\EventListener;

use Brick\Money\Exception\MoneyMismatchException;
use Drago\Commerce\Event\ProductAddedToCart;


/**
 * Listener that applies a discount to the product's final price
 * when a product is added to the cart.
 */
class DiscountListener
{
	/**
	 * Applies discount to the event's final price if applicable.
	 *
	 * @throws MoneyMismatchException
	 */
	public function __invoke(ProductAddedToCart $event): void
	{
		$product = $event->product;
		$price = $event->getFinalPrice();
		$discountPercent = $product->discount ?? 0;

		if ($discountPercent > 0) {
			$discountAmount = $price->multipliedBy($discountPercent)->dividedBy(100);
			$price = $price->minus($discountAmount);
			$event->setFinalPrice($price);
		}
	}
}
