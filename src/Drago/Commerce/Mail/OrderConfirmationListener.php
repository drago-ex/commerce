<?php

declare(strict_types=1);

namespace Drago\Commerce\Mail;

use Brick\Money\Money;
use Drago\Commerce\Commerce;
use Drago\Commerce\Domain\Product\ProductCart;
use Drago\Commerce\Event\OrderPlaced;
use Throwable;
use Tracy\Debugger;


readonly class OrderConfirmationListener
{
	public function __construct(
		private OrderConfirmationMailer $mailer,
	) {
	}


	public function __invoke(OrderPlaced $event): void
	{
		try {
			$currency = $event->orderSummary->currency;
			$items = array_map(
				static function (ProductCart $item): OrderConfirmationItem {
					$unitPrice = $item->getUnitPrice();
					$quantity = $item->amount->toInt();

					return new OrderConfirmationItem(
						productName: mb_substr($item->product->name, 0, 100),
						variantLabel: $item->variantLabel !== null ? mb_substr($item->variantLabel, 0, 255) : null,
						quantity: $quantity,
						unitPrice: Commerce::formatMoney($unitPrice),
						rowTotal: Commerce::formatMoney($unitPrice->multipliedBy($quantity)),
					);
				},
				array_values($event->items),
			);

			$formatAmount = static fn(float $amount): string => Commerce::formatMoney(Money::of($amount, $currency));
			$this->mailer->send(new OrderConfirmation(
				orderId: $event->orderId,
				customer: $event->customer,
				items: $items,
				carrierName: $event->orderSummary->carrier_name,
				paymentName: $event->orderSummary->payment_name,
				subtotalPrice: $formatAmount($event->orderSummary->subtotal_price),
				discountCode: $event->orderSummary->discount_code,
				discountAmount: $formatAmount($event->orderSummary->discount_amount),
				carrierPrice: $formatAmount($event->orderSummary->carrier_price),
				paymentPrice: $formatAmount($event->orderSummary->payment_price),
				totalPrice: $formatAmount($event->orderSummary->total_price),
			), $event->lang);
		} catch (Throwable $e) {
			Debugger::log($e, 'commerce-order-email');
		}
	}
}
