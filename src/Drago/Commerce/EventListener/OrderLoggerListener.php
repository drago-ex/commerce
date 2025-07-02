<?php

declare(strict_types=1);

namespace Drago\Commerce\EventListener;

use Drago\Commerce\Event\OrderPlaced;
use Tracy\Debugger;


/**
 * A listener that responds to the OrderPlaced event and writes
 * detailed order information to the log (including customer,
 * shipping, payment, items, price, and creation time).
 */
class OrderLoggerListener
{
	public function __invoke(OrderPlaced $event): void
	{
		$orderLog = [
			'Order ID' => $event->orderId,
			'Customer' => [
				'name'  => $event->customer->name . ' ' . $event->customer->surname,
				'email' => $event->customer->email,
				'phone' => (string) $event->customer->phone,
			],
			'Carrier' => [
				'name'  => $event->carrier->name ?? '',
				'price' => $event->orderSummary->carrier_price,
			],
			'Payment' => [
				'name'  => $event->payment->name ?? '',
				'price' => $event->orderSummary->payment_price,
			],
			'Items' => array_map(static fn($item): array => [
				'product' => $item->product->name,
				'amount'  => $item->amount->toInt(),
			], $event->shoppingCartSession->getItems()),
			'Total' => $event->orderSummary->total_price,
			'Created at' => $event->orderSummary->created_at->format('Y-m-d H:i:s'),
		];

		Debugger::log($orderLog, 'order.log');
	}
}
