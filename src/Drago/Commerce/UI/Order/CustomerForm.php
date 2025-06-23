<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Commerce\UI\Order;

use Nepada\Bridges\PhoneNumberInputForms\PhoneNumberInputMixin;
use Nette\Application\UI\Form;


/**
 * Customer form with phone number input integration.
 * Extends Nette Form and uses PhoneNumberInputMixin for phone field support.
 */
class CustomerForm extends Form
{
	use PhoneNumberInputMixin;
}
