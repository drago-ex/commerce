<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Commerce\UI\Order;

use Brick\Postcode\InvalidPostcodeException;
use Brick\Postcode\PostcodeFormatter;
use Brick\Postcode\UnknownCountryException;
use Drago\Commerce\Commerce;
use Drago\Commerce\Data\ReaderGeoLite;
use Drago\Commerce\Domain\Customer\Customer;
use Drago\Commerce\Service\OrderSession;
use Drago\Commerce\Service\ShoppingCartSession;
use Drago\Commerce\UI\BaseControl;
use MaxMind\Db\Reader\InvalidDatabaseException;
use Nepada\PhoneNumberInput\PhoneNumberInput;
use Nette\Application\AbortException;


/**
 * @property-read CustomerTemplate $template
 */
class CustomerControl extends BaseControl
{
	public function __construct(
		private readonly ShoppingCartSession $shoppingCartSession,
		private readonly OrderSession $orderSession,
		private readonly ReaderGeoLite $readerGeoLite,
		private readonly Commerce $commerce,
	) {
	}


	public function render(): void
	{
		$template = $this->template;
		$template->setFile($this->templateControl ?: __DIR__ . '/Customer.latte');
		$template->shoppingCart = $this->shoppingCartSession->getItems();
		$template->breadcrumbs = $this->getBreadcrumbs();

		$customer = $this->orderSession
			->getItems()
			->customer;

		if ($customer !== null) {
			$form = $this->getComponent('customer');
			if (!$form->isSubmitted()) {
				$buttonSend = $this->getFormComponent($form, 'send');
				$buttonSend->setCaption('Update');
				$form->setDefaults($customer);
			}
		}

		$template->render();
	}


	/**
	 * @throws InvalidDatabaseException
	 */
	protected function createComponentCustomer(): CustomerForm
	{
		$form = new CustomerForm;
		$form->addText(CustomerData::Email, 'Email')
			->setHtmlAttribute('autocomplete', 'off')
			->addRule($form::Email)
			->setRequired();

		$phone = $form->addPhoneNumber('phone', 'Phone')
			->setRequired()
			->setHtmlAttribute('autocomplete', 'off')
			->setHtmlAttribute('pattern', null);

		$defaultRegionCode = $this->commerce->getDefaultRegionCode();
		if ($defaultRegionCode) {
			is_array($defaultRegionCode) && in_array('autoDetect', $defaultRegionCode, true)
				? $phone->setDefaultRegionCode($this->readerGeoLite->getCountryIsoCode() ?? $defaultRegionCode[1])
				: $phone->setDefaultRegionCode($defaultRegionCode);
		}

		$allowedPhoneNumber = $this->commerce->getAllowedRegionPhoneNumber();
		if ($allowedPhoneNumber) {
			$phone->addRule(PhoneNumberInput::REGION, 'Only phone numbers are allowed', $allowedPhoneNumber);
		}

		$form->addText(CustomerData::Name, 'Name')
			->setHtmlAttribute('autocomplete', 'given-name')
			->setRequired();

		$form->addText(CustomerData::Surname, 'Surname')
			->setHtmlAttribute('autocomplete', 'family-name')
			->setRequired();

		$form->addText(CustomerData::Street, 'Street')
			->setHtmlAttribute('autocomplete', 'off')
			->setRequired();

		$form->addText(CustomerData::City, 'City')
			->setHtmlAttribute('autocomplete', 'off')
			->setRequired();

		$form->addText(CustomerData::Country, 'Country')
			->setHtmlAttribute('autocomplete', 'off')
			->setRequired();

		$form->addText(CustomerData::PostalCode, 'Postal code')
			->setHtmlAttribute('autocomplete', 'off')
			->setRequired();

		$form->addTextArea(CustomerData::Note, 'Note');

		$form->addProtection('The form has expired! Please try again.');
		$form->addSubmit('send', 'Continue');
		$form->onSuccess[] = $this->success(...);

		return $form;
	}


	/**
	 * Handle successful form submission.
	 *
	 * @param CustomerForm $form
	 * @param CustomerData $data
	 * @throws AbortException
	 */
	public function success(CustomerForm $form, CustomerData $data): void
	{
		try {
			$postCode  = $this->commerce->getPostCodeOnRegionPhone()
				? (new PostcodeFormatter)->format($data->phone->getRegionCode(), $data->postal_code)
				: $data->postal_code;

			$customer = new Customer(
				email: $data->email,
				phone: $data->phone,
				name: $data->name,
				surname: $data->surname,
				street: $data->street,
				city: $data->city,
				postal_code: $postCode,
				country: $data->country,
				note: $data->note,
			);

			$this->orderSession->addItemCustomer($customer);
			$this->getPresenter()->redirect($this->linkRedirectTarget);

		} catch (InvalidPostcodeException | UnknownCountryException $e) {
			$form->addError('The postal code does not match the same region as the phone number.');
		}
	}
}
