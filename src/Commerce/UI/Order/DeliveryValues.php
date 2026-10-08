<?php

declare(strict_types=1);

namespace Drago\Commerce\UI\Order;

use Nette\Utils\ArrayHash;


/**
 * Holds the carrier and payment IDs selected in the delivery form.
 */
class DeliveryValues extends ArrayHash
{
	public const string
		CarrierId = 'carrierId',
		PaymentId = 'paymentId';

	public int $carrierId;
	public int $paymentId;
}
