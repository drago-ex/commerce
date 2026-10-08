<?php

declare(strict_types=1);

namespace Drago\Commerce\Mail;

use Brick\Money\Money;
use Drago\Commerce\Commerce;
use Drago\Commerce\Domain\Product\ProductCart;
use Drago\Commerce\Event\OrderPlaced;
use Throwable;
use Tracy\Debugger;


readonly class OrderEmailListener
{
	public function __construct(
		private OrderEmail $orderEmail,
	) {
	}


	/**
	 * A failed email is logged and never affects the placed order or other listeners.
	 */
	public function __invoke(OrderPlaced $event): void
	{
		try {
			$currency = $event->orderSummary->currency;
			$items = array_map(
				static function (ProductCart $item): OrderEmailItem {
					$unitPrice = $item->getUnitPrice();
					$quantity = $item->amount->toInt();

					return new OrderEmailItem(
						productName: $item->product->name,
						variantLabel: $item->variantLabel,
						quantity: $quantity,
						unitPrice: Commerce::formatMoney($unitPrice),
						rowTotal: Commerce::formatMoney($unitPrice->multipliedBy($quantity)),
					);
				},
				array_values($event->items),
			);

			$format = static fn(float $amount): string => Commerce::formatMoney(Money::of($amount, $currency));
			$summary = $event->orderSummary;

			$this->orderEmail->send(new OrderEmailData(
				orderId: $event->orderId,
				customer: $event->customer,
				items: $items,
				carrierName: $summary->carrier_name,
				paymentName: $summary->payment_name,
				subtotalPrice: $format($summary->subtotal_price),
				discountCode: $summary->discount_code,
				discountAmount: $format($summary->discount_amount),
				carrierPrice: $format($summary->carrier_price),
				paymentPrice: $format($summary->payment_price),
				totalPrice: $format($summary->total_price),
			), $event->lang);
		} catch (Throwable $e) {
			Debugger::log($e, 'commerce-order-email');
		}
	}
}
