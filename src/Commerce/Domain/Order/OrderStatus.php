<?php declare(strict_types=1);

namespace Drago\Commerce\Domain\Order;


/**
 * The state of an order and the changes allowed from it: a new order is processed, shipped and completed,
 * and can be cancelled until it is shipped. Whether it is paid is kept apart (OrderEntity::$paidAt),
 * as a bank transfer is paid before shipping and cash on delivery after it.
 */
enum OrderStatus: string
{
	case New = 'new';
	case Processing = 'processing';
	case Shipped = 'shipped';
	case Completed = 'completed';
	case Cancelled = 'cancelled';


	/**
	 * The states the order can change to.
	 * @return list<self>
	 */
	public function next(): array
	{
		return match ($this) {
			self::New => [self::Processing, self::Cancelled],
			self::Processing => [self::Shipped, self::Cancelled],
			self::Shipped => [self::Completed],
			self::Completed, self::Cancelled => [],
		};
	}


	public function canChangeTo(self $status): bool
	{
		return in_array($status, $this->next(), true);
	}
}
