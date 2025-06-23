<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Commerce\UI\Order;

use Drago\Commerce\Domain\Order\OrderDraft;
use Drago\Commerce\UI\BaseTemplate;


/**
 * Template variables for the order summary view.
 */
class SummaryTemplate extends BaseTemplate
{
	public OrderDraft $orderDraft;
}
