<?php

declare(strict_types=1);

namespace Drago\Commerce\Domain\Order;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Brick\Money\Money;


/**
 * The order total the customer saw when the summary was rendered. It travels in
 * the confirmation form, so the order is only placed at the price that was shown.
 */
final class ExpectedTotal
{
	public static function format(Money $total): string
	{
		return (string) $total->getAmount();
	}


	/**
	 * True when the total equals the value shown to the customer.
	 */
	public static function matches(?string $expected, Money $total): bool
	{
		if ($expected === null || $expected === '') {
			return false;
		}

		try {
			return BigDecimal::of($expected)->isEqualTo($total->getAmount());
		} catch (MathException) {
			return false;
		}
	}
}
