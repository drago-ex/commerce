<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Commerce\UI;

use Drago\Application\UI\ExtraControl;


/**
 * It manages the list of steps, tracks the current active step, and
 * which steps have been completed. It also provides a method to
 * retrieve a Breadcrumbs object representing this state.
 */
class BaseControl extends ExtraControl
{
	/**
	 * Optional custom template file for rendering the control.
	 */
	public ?string $templateControl = null;

	/**
	 * List of all steps in the navigation.
	 * Associative array where keys are step identifiers and values are labels.
	 *
	 * @var array<string, string>
	 */
	protected array $steps = [];

	/**
	 * Identifier of the currently active step.
	 */
	protected string $currentStep;

	/**
	 * List of identifiers for completed steps.
	 *
	 * @var string[]
	 */
	protected array $completedSteps = [];


	/**
	 * Sets the list of all navigation steps.
	 */
	public function setSteps(array $steps): void
	{
		$this->steps = $steps;
	}


	/**
	 * Sets the current active step identifier.
	 */
	public function setCurrentStep(string $currentStep): void
	{
		$this->currentStep = $currentStep;
	}


	/**
	 * Sets the list of completed steps identifiers.
	 */
	public function setCompletedSteps(array $completedSteps): void
	{
		$this->completedSteps = $completedSteps;
	}


	/**
	 * Creates and returns a Breadcrumbs object representing the current navigation state.
	 */
	public function getBreadcrumbs(): Breadcrumbs
	{
		return new Breadcrumbs(
			steps: $this->steps,
			completedSteps: $this->completedSteps,
			currentStep: $this->currentStep,
		);
	}
}
