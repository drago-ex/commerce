# Drago Commerce
Simple shopping cart.

## Requirements
- PHP 8.0 or higher
- composer

## Installation
```
composer require drago-ex/commerce
```

## Extension registration
```neon
extensions:
	- Nepada\Bridges\PhoneNumberInputDI\PhoneNumberInputExtension
	commerce: Drago\Commerce\DI\CommerceExtension

commerce:

	# Currency selection, ISO currency code or ISO numeric currency code.
	currency: CZK

	# Formats this Money to the given locale.
	moneyFormat: cs_CZ

	# If you do not require the customer to always enter the international telephone prefix for the order,
	# enter the default value according to ISO 3166-1 alpha-2 country codes.
	# We can also use automatic detection with a set default value in case automatic detection fails.
	# In this case we will use the field, the first value must be "autoDetect" the second value according to county codes.
	defaultRegionCode: ['autoDetect', 'CZ']

	# Restricted to specific international telephone numbers only.
	# Specify one or more ISO 3166-1 alpha-2 country codes.
	allowedRegionPhoneNumber: CZ

	# Request a postal code of the same region as your phone number.
	postCodeOnRegionPhone: true
```