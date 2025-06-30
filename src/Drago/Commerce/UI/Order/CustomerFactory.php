<?php

declare(strict_types=1);

namespace Drago\Commerce\UI\Order;


use Drago\Commerce\Commerce;
use Drago\Commerce\Data\ReaderGeoLite;
use Drago\Commerce\UI\BaseForm;
use Drago\Commerce\UI\Factory;
use Drago\Forms\Autocomplete;
use MaxMind\Db\Reader\InvalidDatabaseException;
use Nepada\PhoneNumberInput\PhoneNumberInput;


class CustomerFactory
{
	public function __construct(
		public Factory $factory,
		public Commerce $commerce,
		public ReaderGeoLite $readerGeoLite,
	) {
	}


	/**
	 * @throws InvalidDatabaseException
	 */
	public function addCustomer(): BaseForm
	{
		$form = $this->factory->create();
		$form->addTextInput(
			name: CustomerData::Email,
			label: 'Email',
			type: 'email',
			placeholder: 'Email address',
			required: 'Please enter your email address.',
			rule: $form::Email,
		)->setAutocomplete(Autocomplete::Email);

		$phone = $form->addPhoneNumber(CustomerData::Phone, 'Phone')
			->setHtmlAttribute('autocomplete', Autocomplete::Tel)
			->setHtmlAttribute('placeholder', 'Please enter a phone number')
			->setHtmlAttribute('pattern', null)
			->setRequired();

		$defaultRegionCode = $this->commerce->getDefaultRegionCode();
		$countryIsoCode = $this->readerGeoLite->getCountryIsoCode();

		if ($defaultRegionCode) {
			is_array($defaultRegionCode) && in_array('autoDetect', $defaultRegionCode, true)
				? $phone->setDefaultRegionCode($countryIsoCode ?? $defaultRegionCode[1])
				: $phone->setDefaultRegionCode($defaultRegionCode);
		}

		$allowedPhoneNumber = $this->commerce->getAllowedRegionPhoneNumber();
		if ($allowedPhoneNumber) {
			$phone->addRule(
				PhoneNumberInput::REGION,
				'Only phone numbers are allowed',
				$allowedPhoneNumber
			);
		}

		$form->addTextInput(
			name: CustomerData::Name,
			label: 'Name',
			placeholder: 'Your name',
			required: 'Please enter your name',
		)->setAutocomplete(Autocomplete::GivenName);

		$form->addTextInput(
			name: CustomerData::Surname,
			label: 'Surname',
			placeholder: 'Your surname',
			required: 'Please enter your surname',
		)->setAutocomplete(Autocomplete::FamilyName);

		$form->addTextInput(
			name: CustomerData::Street,
			label: 'Street',
			placeholder: 'Your street',
			required: 'Please enter your street',
		)->setAutocomplete(Autocomplete::AddressLine1);

		$form->addTextInput(
			name: CustomerData::City,
			label: 'City',
			placeholder: 'Your city',
			required: 'Please enter your city',
		)->setAutocomplete(Autocomplete::AddressLevel2);

		$form->addTextInput(
			name: CustomerData::Country,
			label: 'Country',
			placeholder: 'Your country',
			required: 'Please enter your country',
		)->setAutocomplete(Autocomplete::Country);

		$form->addTextInput(
			name: CustomerData::PostalCode,
			label: 'Postal Code',
			placeholder: 'Your postal code',
			required: 'Please enter your postal code',
		)->setAutocomplete(Autocomplete::PostalCode);


		$form->addTextArea(CustomerData::Note, 'Note');
		$form->addProtection('The form has expired! Please try again.');
		$form->addSubmit('send', 'Continue');

		return $form;
	}
}
