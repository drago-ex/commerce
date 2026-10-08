<?php

declare(strict_types=1);

namespace Drago\Commerce\Mail;

use Nette\Bridges\ApplicationLatte\Template;


class OrderEmailTemplate extends Template
{
	public ?string $lang;

	public OrderEmailData $order;

	public string $storeName;

	public string $storeEmail;
}
