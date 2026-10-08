<?php

declare(strict_types=1);

namespace Drago\Commerce\Domain\Delivery;

use Brick\Money\Exception\UnknownCurrencyException;
use Drago\Commerce\Commerce;


/**
 * Converts a CarrierEntity to a domain Carrier object.
 */
readonly class CarrierMapper
{
	public function __construct(
		private Commerce $commerce,
	) {
	}


	/**
	 * @throws UnknownCurrencyException
	 */
	public function map(CarrierEntity $entity): Carrier
	{
		return new Carrier(
			id: $entity->id,
			name: $entity->name,
			price: $this->commerce->moneyOf($entity->price),
		);
	}
}
