<?php

declare(strict_types=1);

namespace Drago\Commerce\UI\Order;


use Drago\Commerce\Commerce;
use Drago\Commerce\Data\ReaderGeoLite;
use Drago\Commerce\UI\BaseForm;
use Drago\Commerce\UI\Factory;
use Drago\Form\Autocomplete;
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
		$form->addEmailInput(CustomerValues::Email, 'Email')
			->setAutocomplete(Autocomplete::Email)
			->addRule($form::Email);

		$phone = $form->addPhoneNumber(CustomerValues::Phone, 'Phone')
			->setHtmlAttribute('autocomplete', Autocomplete::Tel)
			->setHtmlAttribute('placeholder', 'Please enter a phone number')
			->setHtmlAttribute('pattern', null)
			->setRequired();

		$defaultRegionCode = $this->commerce->getDefaultRegionCode();
		$countryIsoCode = $this->readerGeoLite->getCountryIsoCode();

		if ($defaultRegionCode) {
			if (is_array($defaultRegionCode) && in_array('autoDetect', $defaultRegionCode, true)) {
				$fallback = isset($defaultRegionCode[1]) && is_string($defaultRegionCode[1]) ? $defaultRegionCode[1] : null;
				$phone->setDefaultRegionCode($countryIsoCode ?? $fallback);
			} elseif (is_string($defaultRegionCode)) {
				$phone->setDefaultRegionCode($defaultRegionCode);
			}
		}

		$allowedPhoneNumber = $this->commerce->getAllowedRegionPhoneNumber();
		if ($allowedPhoneNumber) {
			$phone->addRule(
				PhoneNumberInput::REGION,
				'Only phone numbers are allowed',
				$allowedPhoneNumber,
			);
		}

		$form->addTextInput(CustomerValues::Name, 'Name')
			->setAutocomplete(Autocomplete::GivenName)
			->setPlaceholder('Your name')
			->setRequired('Please enter your name');

		$form->addTextInput(CustomerValues::Surname, 'Surname')
			->setAutocomplete(Autocomplete::FamilyName)
			->setPlaceholder('Your surname')
			->setRequired('Please enter your surname');

		$form->addTextInput(CustomerValues::Street, 'Street')
			->setAutocomplete(Autocomplete::AddressLine1)
			->setPlaceholder('Your street')
			->setRequired('Please enter your street');

		$form->addTextInput(CustomerValues::City, 'City')
			->setAutocomplete(Autocomplete::AddressLevel2)
			->setPlaceholder('Your city')
			->setRequired('Please enter your city');

		$form->addTextInput(CustomerValues::Country, 'Country')
			->setAutocomplete(Autocomplete::Country)
			->setPlaceholder('Your country')
			->setRequired('Please enter your country');

		$form->addTextInput(CustomerValues::PostalCode, 'Postal Code')
			->setAutocomplete(Autocomplete::PostalCode)
			->setPlaceholder('Your postal code')
			->setRequired('Please enter your postal code');


		$form->addTextArea(CustomerValues::Note, 'Note');
		$form->addSubmit('send', 'Continue');

		return $form;
	}
}
