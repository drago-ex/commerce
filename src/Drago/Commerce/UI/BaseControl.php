<?php

declare(strict_types=1);

namespace Drago\Commerce\UI;

use Drago\Application\UI\ExtraControl;


/**
 * Manages checkout steps and their completion state for breadcrumb rendering.
 */
class BaseControl extends ExtraControl
{
	/**
	 * Optional custom template file for rendering the control.
	 */
	public ?string $templateControl = null;

	/**
	 * Redirect to the next step of the order.
	 */
	public string $linkRedirectTarget;

	/**
	 * Checkout step labels keyed by action identifier.
	 *
	 * @var array<string, string>
	 */
	protected array $steps = [];

	protected string $currentStep = '';

	/** @var list<string> */
	protected array $completedSteps = [];


	/** @param array<string, string> $steps */
	public function setSteps(array $steps): void
	{
		$this->steps = $steps;
	}


	public function setCurrentStep(string $currentStep): void
	{
		$this->currentStep = $currentStep;
	}


	/** @param list<string> $completedSteps */
	public function setCompletedSteps(array $completedSteps): void
	{
		$this->completedSteps = $completedSteps;
	}


	public function getBreadcrumbs(): Breadcrumbs
	{
		return new Breadcrumbs(
			steps: $this->steps,
			completedSteps: $this->completedSteps,
			currentStep: $this->currentStep,
		);
	}


	/**
	 * Sets the action used by the control's navigation link.
	 *
	 * @throws \InvalidArgumentException The target action is empty.
	 */
	public function setLinkRedirectTarget(string $link): void
	{
		if (empty($link)) {
			throw new \InvalidArgumentException('Redirect target link cannot be empty.');
		}
		$this->linkRedirectTarget = $link;
	}
}
