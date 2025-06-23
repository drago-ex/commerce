<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Commerce\UI\Order;

use Nette\Utils\ArrayHash;


/**
 * Data container for delivery form data.
 */
class DeliveryData extends ArrayHash
{
	public const string
		CarrierId = 'carrierId',
		PaymentId = 'paymentId';

	public int $carrierId;
	public int $paymentId;
}
