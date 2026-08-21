<?php

declare(strict_types=1);

namespace Drago\Commerce\UI;

use Drago\Form\Forms;
use Nepada\Bridges\PhoneNumberInputForms\PhoneNumberInputMixin;


/**
 * Base form class for the Commerce module with phone number input support.
 */
class BaseForm extends Forms
{
	use PhoneNumberInputMixin;
}
