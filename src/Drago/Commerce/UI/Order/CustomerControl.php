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
use Drago\Commerce\Domain\Customer\Customer;
use Drago\Commerce\Service\OrderSession;
use Drago\Commerce\Service\ShoppingCartSession;
use Drago\Commerce\UI\BaseControl;
use Drago\Commerce\UI\BaseForm;
use MaxMind\Db\Reader\InvalidDatabaseException;
use Nette\Application\AbortException;
use Nette\Application\UI\Form;


/**
 * @property-read CustomerTemplate $template
 */
class CustomerControl extends BaseControl
{
	public function __construct(
		private readonly ShoppingCartSession $shoppingCartSession,
		private readonly OrderSession $orderSession,
		private readonly Commerce $commerce,
		private readonly CustomerFactory $customerFactory,
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
	protected function createComponentCustomer(): BaseForm
	{
		$form = $this->customerFactory->addCustomer();
		$form->onSuccess[] = $this->success(...);

		return $form;
	}


	/**
	 * Handle successful form submission.
	 *
	 * @throws AbortException
	 */
	public function success(Form $form, CustomerData $data): void
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
