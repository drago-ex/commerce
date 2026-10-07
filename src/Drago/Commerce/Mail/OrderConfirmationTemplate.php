<?php

declare(strict_types=1);

namespace Drago\Commerce\Mail;

use Nette\Bridges\ApplicationLatte\Template;


class OrderConfirmationTemplate extends Template
{
	public string $lang;

	public OrderConfirmation $order;

	public string $storeName;

	public string $storeEmail;
}
