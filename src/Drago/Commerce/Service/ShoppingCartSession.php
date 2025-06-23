<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

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


/**
 * Represents the shopping cart in the session.
 * Manages products added by the customer, including quantity and total price calculation.
 */
class ShoppingCartSession
{
	/** Session section key for cart items */
	private const string Items = 'items';

	private SessionSection $sessionSection;


	public function __construct(
		private readonly Session $session,
		private readonly Commerce $commerce,
	) {
		$this->sessionSection = $this->session
			->getSection(self::class)
			->setExpiration('1 day');
	}


	/**
	 * Returns all items currently in the basket.
	 *
	 * @return ProductCart[] Array of basket items.
	 */
	public function getItems(): array
	{
		return $this->sessionSection->get(self::Items) ?? [];
	}


	/**
	 * Calculates total price of all items in the basket.
	 *
	 * @throws MoneyMismatchException If currencies don't match during calculation.
	 */
	public function getTotalPrice(): Money
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
	 * @param Product $product Product to add.
	 * @param int $amount Quantity to add (default 1).
	 * @param bool $dontCount If true, sets amount instead of adding.
	 */
	public function addItem(Product $product, int $amount = 1, bool $dontCount = false): void
	{
		$items = $this->getItems();

		foreach ($items as $item) {
			if ($item->product->id === $product->id) {
				if ($dontCount) {
					$item->amount = BigInteger::of($amount);
				} else {
					$item->amount = $item->amount->plus($amount);
				}

				$this->sessionSection->set(self::Items, $items);

				return;
			}
		}

		// Product isn't found in a basket, add new
		$items[] = new ProductCart($product, BigInteger::of($amount));
		$this->sessionSection->set(self::Items, $items);
	}


	/**
	 * Removes a product from the basket.
	 *
	 * @param Product $product Product to remove.
	 */
	public function removeItem(Product $product): void
	{
		$items = [];

		foreach ($this->getItems() as $item) {
			if ($item->product->id !== $product->id) {
				$items[] = $item;
			}
		}

		$this->sessionSection->set(self::Items, $items);
	}


	/**
	 * Empties the basket.
	 */
	public function remove(): void
	{
		$this->sessionSection->remove(self::Items);
	}
}
