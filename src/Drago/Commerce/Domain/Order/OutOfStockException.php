<?php

declare(strict_types=1);

namespace Drago\Commerce\Domain\Order;


/**
 * Not enough stock is left for the requested quantity.
 */
final class OutOfStockException extends OrderException
{
	public function __construct(
		public readonly string $productName,
	) {
		parent::__construct("The product '$productName' is not in stock in the requested quantity.");
	}
}
