<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Commerce\Domain\Customer;

use Brick\PhoneNumber\PhoneNumber;
use DateTimeImmutable;


/**
 * Details about the customer.
 */
class Customer
{
	public function __construct(
		public string $email,
		public PhoneNumber|string $phone,
		public string $name,
		public string $surname,
		public string $street,
		public string $city,
		public string $postal_code,
		public string $country,
		public ?string $note = null,
		public ?DateTimeImmutable $created_at = null,
	) {
		$this->created_at ??= new DateTimeImmutable;
	}
}
