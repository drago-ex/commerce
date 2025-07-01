<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Commerce\UI;

use Drago\Form\Forms;
use Nepada\Bridges\PhoneNumberInputForms\PhoneNumberInputMixin;


class BaseForm extends Forms
{
	use PhoneNumberInputMixin;
}
