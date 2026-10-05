<?php

declare(strict_types=1);

namespace Drago\Commerce\Service;

use Brick\Math\BigInteger;
use Brick\Money\Exception\MoneyMismatchException;
use Brick\Money\Money;
use Drago\Commerce\Commerce;
use Drago\Commerce\Domain\Product\Product;
use Drago\Commerce\Domain\Product\ProductCart;
use Nette\Http\Session;
use Nette\Http\SessionSection;


/** Stores cart items in the session and calculates their totals. */
class ShoppingCartSession
{
	private const string Items = 'items';

	private SessionSection $sessionSection;


	public function __construct(
		private readonly Session $session,
		private readonly Commerce $commerce,
		private readonly DiscountCodeService $discountCodeService,
	) {
		$this->sessionSection = $this->session
			->getSection(self::class)
			->setExpiration('1 day');
	}


	/** @return ProductCart[] */
	public function getItems(): array
	{
		return $this->sessionSection->get(self::Items) ?? [];
	}


	/**
	 * Calculates total price of all items in the basket, after per-product
	 * discounts and after the discount code (voucher), if any is applied.
	 *
	 * @throws MoneyMismatchException If currencies don't match during calculation.
	 */
	public function getTotalPrice(): Money
	{
		return $this->discountCodeService->applyTo($this->getSubtotalPrice());
	}


	/**
	 * Returns the cart total after per-product discounts but before applying a discount code.
	 *
	 * @throws MoneyMismatchException If currencies don't match during calculation.
	 */
	public function getSubtotalPrice(): Money
	{
		$totalPrice = $this->commerce->moneyZero();

		foreach ($this->getItems() as $item) {
			$totalPrice = $totalPrice->plus(
				$item->product->getDiscountedPrice()->multipliedBy($item->amount),
			);
		}

		return $totalPrice;
	}


	/**
	 * Returns the cart total at full (undiscounted) unit prices, ignoring both
	 * per-product discounts and any discount code. Used to show how much the
	 * per-product discounts are saving the customer.
	 *
	 * @throws MoneyMismatchException If currencies don't match during calculation.
	 */
	public function getOriginalPrice(): Money
	{
		$totalPrice = $this->commerce->moneyZero();

		foreach ($this->getItems() as $item) {
			$totalPrice = $totalPrice->plus(
				$item->product->price->multipliedBy($item->amount),
			);
		}

		return $totalPrice;
	}


	/**
	 * Returns total quantity of all items in the basket.
	 */
	public function getAmountItems(): int
	{
		$amountItems = BigInteger::zero();

		foreach ($this->getItems() as $item) {
			$amountItems = $amountItems->plus($item->amount);
		}

		return $amountItems->toInt();
	}


	/**
	 * Adds product(s) to the basket.
	 *
	 * $amount must be >= 1. If $dontCount is true, sets the item's quantity to $amount; otherwise adds the amount.
	 *
	 * $variantId identifies which variant of the product was chosen (null
	 * for a product without variants). Two lines with the same product but
	 * a different $variantId are kept as separate cart items — matching is
	 * by product ID *and* variant ID together, not product ID alone.
	 */
	public function addItem(
		Product $product,
		int $amount = 1,
		bool $dontCount = false,
		?int $variantId = null,
		?string $variantLabel = null,
	): void
	{
		$items = $this->getItems();

		foreach ($items as $item) {
			if ($item->product->id === $product->id && $item->variantId === $variantId) {
				if ($dontCount) {
					$item->amount = BigInteger::of($amount);
				} else {
					$item->amount = $item->amount->plus($amount);
				}

				$this->sessionSection->set(self::Items, $items);

				return;
			}
		}

		$items[] = new ProductCart($product, BigInteger::of($amount), $variantId, $variantLabel);
		$this->sessionSection->set(self::Items, $items);
	}


	/**
	 * Returns the cart line for the product (and variant, if any), or null.
	 */
	public function findItem(int $productId, ?int $variantId = null): ?ProductCart
	{
		foreach ($this->getItems() as $item) {
			if ($item->product->id === $productId && $item->variantId === $variantId) {
				return $item;
			}
		}

		return null;
	}


	/**
	 * Returns how many pieces of the product (and variant, if any) are in the cart.
	 */
	public function getAmount(int $productId, ?int $variantId = null): int
	{
		return $this->findItem($productId, $variantId)?->amount->toInt() ?? 0;
	}


	public function removeItem(Product $product, ?int $variantId = null): void
	{
		$this->removeLine($product->id, $variantId);
	}


	/**
	 * Removes the cart line by IDs, so it also works for a product that has
	 * meanwhile been deleted or deactivated.
	 */
	public function removeLine(int $productId, ?int $variantId = null): void
	{
		$items = [];

		foreach ($this->getItems() as $item) {
			if (!($item->product->id === $productId && $item->variantId === $variantId)) {
				$items[] = $item;
			}
		}

		$this->sessionSection->set(self::Items, $items);
	}


	public function remove(): void
	{
		$this->sessionSection->remove(self::Items);
	}
}
