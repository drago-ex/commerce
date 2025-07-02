<?php

declare(strict_types=1);

namespace Drago\Commerce\Event;

use Drago\Commerce\Domain\Customer\Customer;


/**
 * Event triggered when customer information is updated.
 */
class CustomerUpdated
{
	public function __construct(
		public Customer $customer,
	) {
	}
}
