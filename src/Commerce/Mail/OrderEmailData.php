<?php declare(strict_types=1);

namespace Drago\Commerce\Mail;

use Drago\Commerce\Domain\Customer\Customer;


final readonly class OrderEmailData
{
	public function __construct(
		public int $orderId,
		public Customer $customer,
		/** @var list<OrderEmailItem> */
		public array $items,
		public string $carrierName,
		public string $paymentName,
		public string $subtotalPrice,
		public ?string $discountCode,
		public string $discountAmount,
		public string $carrierPrice,
		public string $paymentPrice,
		public string $totalPrice,
	) {
	}
}
