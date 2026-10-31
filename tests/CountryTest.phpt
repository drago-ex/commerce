<?php declare(strict_types=1);

use Drago\Commerce\Commerce;
use Tester\Assert;

require __DIR__ . '/bootstrap.php';

// names of the ISO codes come from intl in the language, English without one
Assert::same('Česko', Commerce::countryName('CZ', 'cs'));
Assert::same('Slovakia', Commerce::countryName('SK'));
Assert::same('XX', Commerce::countryName('XX', 'cs'));

// the delivery countries of the configuration, or the default ones
Assert::same(['CZ', 'SK'], (new Commerce([]))->getCountries());
Assert::same(['DE', 'AT'], (new Commerce(['countries' => ['DE', 'AT']]))->getCountries());
Assert::true((new Commerce([]))->getValidatePostCode());
Assert::false((new Commerce(['validatePostCode' => false]))->getValidatePostCode());
