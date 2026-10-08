<?php

declare(strict_types=1);

namespace Drago\Commerce\Domain\Product;

use Brick\Money\Exception\MoneyMismatchException;
use Brick\Money\Exception\UnknownCurrencyException;
use Drago\Commerce\Commerce;
use Drago\Commerce\Event\EventDispatcher;
use Drago\Commerce\Event\ProductAddedToCart;


/**
 * Single place that decides what a cart line costs: the variant's own price
 * (sold without the product discount) or the product price with its percentage
 * discount, then the ProductAddedToCart listeners.
 */
class PriceResolver
{
	public function __construct(
		private readonly Commerce $commerce,
		private readonly EventDispatcher $eventDispatcher,
	) {
	}


	/**
	 * Current catalog price, before any listener runs.
	 *
	 * @throws UnknownCurrencyException
	 */
	public function catalog(ProductEntity $entity, ?ProductVariantEntity $variant = null): CatalogPrice
	{
		if ($variant !== null && $variant->hasPriceOverride()) {
			return new CatalogPrice($this->commerce->moneyOf((float) $variant->price));
		}

		return new CatalogPrice($this->commerce->moneyOf($entity->price), $entity->discount);
	}


	/**
	 * Builds the product for a cart line and dispatches ProductAddedToCart.
	 * The percentage discount is applied only when no listener changed the price.
	 *
	 * @throws UnknownCurrencyException
	 * @throws MoneyMismatchException
	 */
	public function forCart(
		ProductEntity $entity,
		?ProductVariantEntity $variant,
		?string $variantLabel,
		int $amount,
	): Product
	{
		$catalog = $this->catalog($entity, $variant);
		$product = new Product(id: $entity->id, name: $entity->name, price: $catalog->price);

		$event = new ProductAddedToCart($product, $product->price, $variant?->id, $variantLabel, $amount);
		$this->eventDispatcher->dispatch($event);

		$item = new Product(id: $entity->id, name: $entity->name, price: $event->getPrice());
		if ($catalog->discount !== null && $event->getPrice()->isEqualTo($catalog->price)) {
			$item->setDiscount($catalog->discount);
		}

		$item->catalog = $catalog;
		return $item;
	}
}
