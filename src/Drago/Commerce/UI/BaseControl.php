<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Commerce\UI;

use Drago\Application\UI\ExtraControl;


class BaseControl extends ExtraControl
{
	/** Custom control template */
	public ?string $templateControl = null;
	protected array $steps = [];
	protected string $currentStep = '';
	protected array $completedSteps = [];


	public function setSteps(array $steps): void
	{
		$this->steps = $steps;
	}


	public function setCurrentStep(string $currentStep): void
	{
		$this->currentStep = $currentStep;
	}


	public function setCompletedSteps(array $completedSteps): void
	{
		$this->completedSteps = $completedSteps;
	}

}
