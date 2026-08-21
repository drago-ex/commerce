<?php

declare(strict_types=1);

namespace Drago\Commerce\UI;


/**
 * Represents the breadcrumb navigation state for a multistep process.
 *
 * This class holds the list of all steps, which steps are completed,
 * and the current active step, used to render breadcrumbs in the UI.
 */
class Breadcrumbs
{
	/**
	 * @param array<string, string> $steps
	 * @param string[] $completedSteps
	 */
	public function __construct(
		public array $steps,
		public array $completedSteps,
		public string $currentStep,
	) {
	}
}
