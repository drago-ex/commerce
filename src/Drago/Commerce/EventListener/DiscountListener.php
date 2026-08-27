<?php

declare(strict_types=1);

namespace Drago\Commerce\EventListener;

use Drago\Commerce\Event\ProductAddedToCart;


/**
 * Listener slot for the ProductAddedToCart event.
 *
 * NOTE: The standard percentage discount (Product::$discount) is applied
 * automatically wherever the price is needed via Product::getDiscountedPrice()
 * (see the Discount trait) — the cart, the order total and the order line items
 * all read it from there. This listener must NOT also subtract the percentage
 * from $event's price: the item added to the cart keeps its original price plus
 * the discount%, and baking the percentage in here as well would apply it twice.
 *
 * Use this listener only for pricing rules that are NOT already covered by the
 * standard percentage discount (e.g. customer-specific pricing), by calling
 * $event->setPrice() with the final price you want that item to have.
 */
class DiscountListener
{
	public function __invoke(ProductAddedToCart $event): void
	{
		// No standard-discount logic here anymore — see the class docblock.
	}
}
