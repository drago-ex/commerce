<?php

declare(strict_types=1);

namespace Drago\Commerce\Domain\Checkout;

use Drago\Commerce\Service\OrderSession;
use Drago\Commerce\Service\ShoppingCartSession;


/**
 * Determines which checkout step to redirect to based on the current order state.
 */
final readonly class CheckoutRedirectResolver
{
	public function __construct(
		private ShoppingCartSession $shoppingCartSession,
		private OrderSession $orderSession,
		private CheckoutSteps $checkoutSteps,
	) {
	}


	public function getRedirectTargetForAction(string $action): ?string
	{
		$orderDraft = $this->orderSession->getItems();
		$hasItems = $this->shoppingCartSession->getAmountItems() > 0;
		$carrier = $orderDraft->carrier;
		$customer = $orderDraft->customer;

		$step = $this->checkoutSteps;

		return match ($action) {
			$step->delivery => (!$hasItems && $action !== $step->products)
				? $step->products
				: null,

			$step->customer => match (true) {
				$carrier === null => $hasItems ? $step->delivery : $step->products,
				default => null,
			},

			$step->summary => match (true) {
				$carrier === null => $hasItems ? $step->delivery : $step->products,
				$customer === null => $step->customer,
				default => null,
			},

			default => null,
		};
	}
}
