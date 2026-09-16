<?php

declare(strict_types=1);

namespace Drago\Commerce\Domain\Checkout;

use Drago\Commerce\Service\OrderSession;
use Drago\Commerce\Service\ShoppingCartSession;
use Drago\Commerce\UI\BaseControl;


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


	/**
	 * Returns the step that follows the given one in the checkout flow, or
	 * null when there isn't one (e.g. the given step is the last, or unknown).
	 */
	public function getNextStep(string $step): ?string
	{
		return $this->checkoutSteps->next($step);
	}


	/**
	 * Fully configures a step control for its place in the checkout flow:
	 * the step map (for breadcrumbs), which steps are already completed,
	 * which one is current, and where to send the customer next.
	 *
	 * Used by CommerceControl to wire each control up once, at injection
	 * time — a control's step is fixed by what it is (DeliveryControl is
	 * always the delivery step), so there's nothing presenter-specific
	 * about this and no reason to repeat it in every presenter.
	 */
	public function configureStep(BaseControl $control, string $step): BaseControl
	{
		$control->setSteps($this->getSteps());
		$control->setCompletedSteps($this->getCompletedSteps());
		$control->setCurrentStep($step);

		$next = $this->getNextStep($step);
		if ($next !== null) {
			$control->setLinkRedirectTarget($next);
		}

		return $control;
	}


	public function steps(): CheckoutSteps
	{
		return $this->checkoutSteps;
	}
}
