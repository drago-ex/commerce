<?php

declare(strict_types=1);

namespace Drago\Commerce\Service;

use Brick\Money\Exception\MoneyMismatchException;
use Brick\Money\Money;
use Drago\Commerce\Domain\DiscountCode\DiscountCodeEntity;
use Drago\Commerce\Domain\Product\ProductCart;


/**
 * Prices of the cart as shown by every checkout step.
 */
final readonly class CartTotals
{
	/**
	 * @param ProductCart[] $items
	 */
	public function __construct(
		public array $items,
		public int $amountItems,
		public Money $originalPrice,
		public Money $subtotalPrice,
		public Money $productDiscountAmount,
		public Money $discountAmount,
		public Money $cartTotalPrice,
		public ?DiscountCodeEntity $discountCode,
	) {
	}


	/**
	 * The cart total plus the given extra prices (delivery, payment).
	 *
	 * @throws MoneyMismatchException
	 */
	public function withExtras(Money ...$extras): Money
	{
		$total = $this->cartTotalPrice;
		foreach ($extras as $extra) {
			$total = $total->plus($extra);
		}

		return $total;
	}
}
