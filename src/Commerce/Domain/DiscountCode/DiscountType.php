<?php declare(strict_types=1);

namespace Drago\Commerce\Domain\DiscountCode;


/** How the value of a discount code is applied: a percentage of the total or a fixed amount. */
enum DiscountType: string
{
	case Percent = 'percent';
	case Fixed = 'fixed';
}
