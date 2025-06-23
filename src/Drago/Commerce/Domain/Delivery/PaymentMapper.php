<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Commerce\Domain\Delivery;

use Brick\Money\Exception\UnknownCurrencyException;
use Drago\Commerce\Commerce;


/**
 * Converts PaymentEntity to a domain Payment object.
 */
readonly class PaymentMapper
{
	public function __construct(
		private Commerce $commerce,
	) {
	}


	/**
	 * @throws UnknownCurrencyException
	 */
	public function map(PaymentEntity $entity): Payment
	{
		return new Payment(
			id: $entity->id,
			name: $entity->name,
			price: $this->commerce->moneyOf($entity->price),
		);
	}
}
