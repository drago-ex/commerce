<?php declare(strict_types=1);

namespace Drago\Commerce\Service;

use Dibi\Exception;
use Drago\Commerce\Domain\DiscountCode\DiscountCodeRepository;
use Drago\Commerce\Domain\Order\OrderProductRepository;
use Drago\Commerce\Domain\Order\OrderRepository;
use Drago\Commerce\Domain\Order\OrderStatus;
use Drago\Commerce\Domain\Order\OrderStatusException;
use Drago\Commerce\Domain\Order\StockReservation;
use Drago\Commerce\Event\EventDispatcher;
use Drago\Commerce\Event\OrderStatusChanged;


/**
 * Changes the state of an order along OrderStatus::next() and records its payment. A cancelled order returns
 * its items to stock and the use of its discount code, in the same transaction as the change of the state.
 */
readonly class OrderStatusService
{
	public function __construct(
		private OrderRepository $orderRepository,
		private OrderProductRepository $orderProductRepository,
		private StockReservation $stockReservation,
		private DiscountCodeRepository $discountCodeRepository,
		private EventDispatcher $eventDispatcher,
	) {
	}


	/**
	 * @throws OrderStatusException The order does not exist, or its state cannot change to the given one.
	 * @throws \Throwable
	 */
	public function change(int $orderId, OrderStatus $status): void
	{
		$previous = $this->orderRepository->table->transaction(function () use ($orderId, $status): OrderStatus {
			$order = $this->orderRepository->getForUpdate($orderId)
				?? throw new OrderStatusException("The order $orderId does not exist.");

			if (!$order->status->canChangeTo($status)) {
				throw new OrderStatusException(
					"The order $orderId cannot change from '{$order->status->value}' to '$status->value'.",
				);
			}

			if ($status === OrderStatus::Cancelled) {
				$this->stockReservation->release($this->orderProductRepository->getByOrder($orderId));
				if ($order->discountCode !== null) {
					$this->discountCodeRepository->decrementUsage($order->discountCode);
				}
			}

			$this->orderRepository->setStatus($orderId, $status);
			return $order->status;
		});

		// after the commit, so a listener never sees a state that is rolled back
		$this->eventDispatcher->dispatch(new OrderStatusChanged($orderId, $previous, $status));
	}


	/**
	 * Records the payment of the order; returns false when it was already paid or does not exist.
	 * @throws Exception
	 */
	public function markPaid(int $orderId): bool
	{
		return $this->orderRepository->markPaid($orderId);
	}
}
