<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

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
	public function __construct(
		public array $steps,
		public array $completedSteps,
		public string $currentStep,
	) {
	}
}
