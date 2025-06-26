<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Commerce\Domain\Checkout;

use Drago\Commerce\Service\OrderSession;
use Drago\Commerce\Service\ShoppingCartSession;


/**
 * Determines if a redirect is needed during checkout,
 * based on the current step and order/cart state.
 */
final readonly class CheckoutRedirectResolver
{
	public function __construct(
		private ShoppingCartSession $shoppingCartSession,
		private OrderSession $orderSession,
		private CheckoutSteps $checkoutSteps,
	) {
	}


	/**
	 * Returns the key of the step to redirect to if the current action is not allowed,
	 * or null if no redirect is needed.
	 */
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
				$carrier === null && $hasItems => $step->delivery,
				$carrier === null && !$hasItems => $step->products,
				default => null,
			},

			$step->summary => match (true) {
				$carrier === null && $hasItems => $step->delivery,
				$carrier === null && !$hasItems => $step->products,
				$customer === null && $carrier !== null => $step->customer,
				default => null,
			},

			default => null,
		};
	}
}
