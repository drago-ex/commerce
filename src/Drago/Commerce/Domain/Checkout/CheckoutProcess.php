<?php

declare(strict_types=1);

namespace Drago\Commerce\Domain\Checkout;

use Drago\Commerce\Service\OrderSession;
use Drago\Commerce\Service\ShoppingCartSession;


/**
 * Orchestrates the checkout flow by delegating step resolution and redirect logic.
 */
final class CheckoutProcess
{
	private CheckoutRedirectResolver $redirectResolver;
	private CheckoutStepResolver $stepResolver;


	public function __construct(
		private readonly ShoppingCartSession $shoppingCartSession,
		private readonly OrderSession $orderSession,
		private readonly CheckoutSteps $checkoutSteps,
	) {
		$this->redirectResolver = new CheckoutRedirectResolver(
			$this->shoppingCartSession,
			$this->orderSession,
			$this->checkoutSteps,
		);

		$this->stepResolver = new CheckoutStepResolver(
			$this->shoppingCartSession,
			$this->orderSession,
			$this->checkoutSteps,
		);
	}


	/**
	 * Returns a map of step identifiers to display labels.
	 *
	 * @return array<string, string>
	 */
	public function getSteps(): array
	{
		return $this->checkoutSteps->steps;
	}


	/**
	 * Returns identifiers of completed checkout steps.
	 *
	 * @return list<string>
	 */
	public function getCompletedSteps(): array
	{
		return $this->stepResolver->getCompletedSteps();
	}


	public function getRedirectTargetForAction(string $action): ?string
	{
		return $this->redirectResolver->getRedirectTargetForAction($action);
	}


	public function steps(): CheckoutSteps
	{
		return $this->checkoutSteps;
	}
}
