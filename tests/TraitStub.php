<?php declare(strict_types=1);

use Drago\Commerce\UI\CommerceControl;


/** Lets PHPStan analyse the CommerceControl trait, which no class in the package uses. */
class TraitStub
{
	use CommerceControl;
}
