<?php

declare(strict_types=1);

namespace Drago\Commerce\Domain\Order;


/**
 * A cart item can no longer be ordered: the product or variant was removed
 * or deactivated, or a product that needs a variant has none selected.
 */
final class ItemUnavailableException extends OrderException
{
	public function __construct(
		public readonly string $productName,
	) {
		parent::__construct("The product '$productName' is no longer available.");
	}
}
