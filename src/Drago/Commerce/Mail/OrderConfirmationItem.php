<?php

declare(strict_types=1);

namespace Drago\Commerce\Mail;


final readonly class OrderConfirmationItem
{
	public function __construct(
		public string $productName,
		public ?string $variantLabel,
		public int $quantity,
		public string $unitPrice,
		public string $rowTotal,
	) {
	}
}
