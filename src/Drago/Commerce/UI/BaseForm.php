<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare (strict_types=1);

namespace App\Commerce\UI;

use Drago\Forms\Forms;
use Nepada\Bridges\PhoneNumberInputForms\PhoneNumberInputMixin;


class BaseForm extends Forms
{
	use PhoneNumberInputMixin;
}
