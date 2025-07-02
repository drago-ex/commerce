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
	 * Applies discount to the event's price if applicable.
	 *
	 * @throws MoneyMismatchException
	 */
	public function __invoke(ProductAddedToCart $event): void
	{
		$product = $event->product;
		$discount = $product->getDiscount();

		if ($discount > 0) {
			$price = $event->getPrice();
			$discountAmount = $price->multipliedBy($discount)->dividedBy(100);
			$discounted = $price->minus($discountAmount);
			$event->setPrice($discounted);
		}
	}
}
