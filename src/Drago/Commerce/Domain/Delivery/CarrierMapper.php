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
 * Service class for converting CarrierEntity to domain Carrier object.
 */
readonly class CarrierMapper
{
	public function __construct(
		private Commerce $commerce,
	) {
	}


	/**
	 * Converts CarrierEntity to a domain Carrier model.
	 *
	 * @throws UnknownCurrencyException When currency conversion fails.
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
