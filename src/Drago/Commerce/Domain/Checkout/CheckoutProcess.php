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
 * Main class managing the checkout process logic.
 *
 * It combines the step resolver and redirect resolver,
 * and provides access to check out steps and their statuses.
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
	 * Returns all checkout steps with their labels.
	 *
	 * @return array<string, string> Associative array of step keys to step names.
	 */
	public function getSteps(): array
	{
		return $this->checkoutSteps->steps;
	}


	/**
	 * Returns the list of completed checkout steps.
	 *
	 * @return string[] List of completed step keys.
	 */
	public function getCompletedSteps(): array
	{
		return $this->stepResolver->getCompletedSteps();
	}


	/**
	 * Determines if a redirect is needed for the given action step.
	 */
	public function getRedirectTargetForAction(string $action): ?string
	{
		return $this->redirectResolver->getRedirectTargetForAction($action);
	}


	/**
	 * Returns the CheckoutSteps instance.
	 */
	public function steps(): CheckoutSteps
	{
		return $this->checkoutSteps;
	}
}
