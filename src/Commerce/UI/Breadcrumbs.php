<?php declare(strict_types=1);

namespace Drago\Commerce\UI;


/**
 * Represents checkout steps and their completion state for breadcrumb rendering.
 */
class Breadcrumbs
{
	public function __construct(
		/** @var array<string, string> */
		public array $steps,
		/** @var list<string> */
		public array $completedSteps,
		public string $currentStep,
	) {
	}
}
