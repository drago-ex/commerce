<?php declare(strict_types=1);

namespace Drago\Commerce\Domain\Order;


/** The order does not exist, or its state cannot change to the requested one. */
final class OrderStatusException extends OrderException
{
}
