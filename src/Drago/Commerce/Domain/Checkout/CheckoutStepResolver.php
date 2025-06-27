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
 * Determines which checkout steps have been completed
 * based on shopping cart and order draft data.
 */
final readonly class CheckoutStepResolver
{
	public function __construct(
		private ShoppingCartSession $shoppingCartSession,
		private OrderSession $orderSession,
		private CheckoutSteps $checkoutSteps,
	) {
	}


	/**
	 * Returns a list of completed checkout steps based on session and order data.
	 *
	 * @return string[] Array of completed step keys (e.g., 'shoppingCart', 'delivery')
	 */
	public function getCompletedSteps(): array
	{
		$hasItems = $this->shoppingCartSession->getAmountItems() > 0;
		$orderDraft = $this->orderSession->getItems();
		$completedSteps = [];
		$step = $this->checkoutSteps;

		if ($hasItems) {
			$completedSteps[] = $step->shoppingCart;
		}

		if ($orderDraft->carrier !== null) {
			$completedSteps[] = $step->delivery;
		}

		if ($orderDraft->customer !== null) {
			$completedSteps[] = $step->customer;
		}

		if (
			$hasItems &&
			$orderDraft->carrier !== null &&
			$orderDraft->customer !== null
		) {
			$completedSteps[] = $step->summary;
		}

		return $completedSteps;
	}
}
