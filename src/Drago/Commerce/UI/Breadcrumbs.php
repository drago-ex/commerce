<?php

declare(strict_types=1);

namespace Drago\Commerce\UI;


/**
 * Represents checkout steps and their completion state for breadcrumb rendering.
 */
class Breadcrumbs
{
	/**
	 * @param array<string, string> $steps
	 * @param list<string> $completedSteps
	 */
	public function __construct(
		public array $steps,
		public array $completedSteps,
		public string $currentStep,
	) {
	}
}
