<?php declare(strict_types=1);

namespace Drago\Commerce\UI\Order;

use Drago\Commerce\Commerce;
use Drago\Commerce\Data\ReaderGeoLite;
use Drago\Commerce\UI\BaseForm;
use Drago\Commerce\UI\Factory;
use Drago\Form\Autocomplete;
use MaxMind\Db\Reader\InvalidDatabaseException;
use Nepada\PhoneNumberInput\PhoneNumberInput;
use Nette\Http\Request;
use Nette\Localization\Translator;
use Nette\Utils\Html;
use function is_array, is_string;


/** Factory for building the customer information form with phone and address fields. */
class CustomerFactory
{
	public function __construct(
		public Factory $factory,
		public Commerce $commerce,
		public ReaderGeoLite $readerGeoLite,
		public Request $httpRequest,
	) {
	}


	/**
	 * @param string|null $lang the language of the country names
	 * @throws InvalidDatabaseException
	 */
	public function addCustomer(?Translator $translator = null, ?string $lang = null): BaseForm
	{
		$form = $this->factory->create($translator);
		$form->addEmailInput(CustomerValues::Email, 'Email')
			->setAutocomplete(Autocomplete::Email)
			->addRule($form::Email)
			->addRule($form::MaxLength, 'The email can have at most %d characters.', CustomerValues::EmailSize);

		$phone = $form->addPhoneNumber(CustomerValues::Phone, 'Phone')
			->setHtmlAttribute('autocomplete', Autocomplete::Tel->value)
			->setHtmlAttribute('placeholder', 'Please enter a phone number')
			->setHtmlAttribute('pattern', null)
			->setRequired();

		$defaultRegionCode = $this->commerce->getDefaultRegionCode();

		$clientIp = $this->httpRequest->getRemoteAddress();
		$countryIsoCode = $clientIp !== null
			? $this->readerGeoLite->getCountryIsoCode($clientIp)
			: null;

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
			->setRequired('Please enter your name')
			->addRule($form::MaxLength, 'The name can have at most %d characters.', CustomerValues::NameSize);

		$form->addTextInput(CustomerValues::Surname, 'Surname')
			->setAutocomplete(Autocomplete::FamilyName)
			->setPlaceholder('Your surname')
			->setRequired('Please enter your surname')
			->addRule($form::MaxLength, 'The surname can have at most %d characters.', CustomerValues::SurnameSize);

		$form->addTextInput(CustomerValues::Street, 'Street')
			->setAutocomplete(Autocomplete::AddressLine1)
			->setPlaceholder('Your street')
			->setRequired('Please enter your street')
			->addRule($form::MaxLength, 'The street can have at most %d characters.', CustomerValues::StreetSize);

		$form->addTextInput(CustomerValues::City, 'City')
			->setAutocomplete(Autocomplete::AddressLevel2)
			->setPlaceholder('Your city')
			->setRequired('Please enter your city')
			->addRule($form::MaxLength, 'The city can have at most %d characters.', CustomerValues::CitySize);

		// the delivery countries of the configuration, named by intl in the language (not translated again)
		$countries = $this->commerce->getCountries();
		$form->addSelect(CustomerValues::Country, 'Country', array_combine(
			$countries,
			array_map(fn(string $code): Html => Html::el()->setText(Commerce::countryName($code, $lang)), $countries),
		))
			->setHtmlAttribute('autocomplete', Autocomplete::Country->value)
			// a country of the session that is no longer offered is not preselected instead of failing
			->checkDefaultValue(false)
			->setDefaultValue(in_array($countryIsoCode, $countries, true) ? $countryIsoCode : $countries[0])
			->setRequired('Please choose your country');

		$form->addTextInput(CustomerValues::PostalCode, 'Postal Code')
			->setAutocomplete(Autocomplete::PostalCode)
			->setPlaceholder('Your postal code')
			->setRequired('Please enter your postal code')
			->addRule($form::MaxLength, 'The postal code can have at most %d characters.', CustomerValues::PostalCodeSize);


		$form->addTextArea(CustomerValues::Note, 'Note');
		$form->addSubmit('send', 'Continue');

		return $form;
	}
}
