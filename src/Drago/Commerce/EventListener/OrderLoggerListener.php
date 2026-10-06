<?php

declare(strict_types=1);

namespace Drago\Commerce\EventListener;

use Drago\Commerce\Event\OrderPlaced;
use Tracy\Debugger;


/**
 * A listener that responds to the OrderPlaced event and writes
 * order information to the log: ID, shipping, payment, items with their variants,
 * prices and creation time. Customer contact details are deliberately not logged;
 * the order is linked to its customer by ID.
 */
class OrderLoggerListener
{
	public function __invoke(OrderPlaced $event): void
	{
		Debugger::log($this->toArray($event), 'order');
	}


	/**
	 * @return array<string, mixed>
	 */
	public function toArray(OrderPlaced $event): array
	{
		$items = $event->items !== [] ? $event->items : $event->shoppingCartSession->getItems();

		return [
			'Order ID' => $event->orderId,
			'Customer ID' => $event->orderSummary->customer_id,
			'Carrier' => [
				'name'  => $event->carrier->name ?? '',
				'price' => $event->orderSummary->carrier_price,
			],
			'Payment' => [
				'name'  => $event->payment->name ?? '',
				'price' => $event->orderSummary->payment_price,
			],
			'Items' => array_map(static fn($item): array => [
				'product_id' => $item->product->id,
				'product'    => $item->product->name,
				'variant_id' => $item->variantId,
				'variant'    => $item->variantLabel,
				'amount'     => $item->amount->toInt(),
				'unit_price' => $item->getUnitPrice()->getAmount()->toFloat(),
			], array_values($items)),
			'Subtotal'        => $event->orderSummary->subtotal_price,
			'Discount code'   => $event->orderSummary->discount_code,
			'Discount amount' => $event->orderSummary->discount_amount,
			'Total'           => $event->orderSummary->total_price,
			'Created at'      => $event->orderSummary->created_at->format('Y-m-d H:i:s'),
		];
	}
}
