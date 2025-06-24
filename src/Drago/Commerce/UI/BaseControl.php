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
}
