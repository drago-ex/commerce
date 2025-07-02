<?php

declare(strict_types=1);

namespace Drago\Commerce\EventListener;

use Drago\Commerce\Event\OrderPlaced;
use Tracy\Debugger;


class OrderLoggerListener
{
	public function __invoke(OrderPlaced $event): void
	{
		$orderLog = [
			'Order ID' => $event->orderSummary->customer_id ?? 'n/a',
			'Customer' => [
				'name' => $event->customer->name . ' ' . $event->customer->surname,
				'email' => $event->customer->email,
				'phone' => (string) $event->customer->phone,
			],
			'Carrier' => $event->carrier->name ?? '',
			'Payment' => $event->payment->name ?? '',
			'Items' => array_map(static fn($item): array => [
					'product' => $item->product->name,
					'amount' => $item->amount->toInt(),
				], $event->shoppingCartSession->getItems()),
			'Total' => $event->orderSummary->total_price,
			'Created at' => $event->orderSummary->created_at->format('Y-m-d H:i:s'),
		];

		Debugger::log($orderLog, 'order.log');
	}
}
