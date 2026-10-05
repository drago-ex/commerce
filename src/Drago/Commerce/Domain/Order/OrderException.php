<?php

declare(strict_types=1);

namespace Drago\Commerce\Domain\Order;

use RuntimeException;


/**
 * An order cannot be completed for a reason the customer can act on
 * (e.g. an item is sold out), as opposed to an unexpected system failure.
 */
class OrderException extends RuntimeException
{
}
