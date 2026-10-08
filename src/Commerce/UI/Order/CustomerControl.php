<?php

declare(strict_types=1);

namespace Drago\Commerce\UI\Order;

use Brick\Money\Exception\MoneyMismatchException;
use Brick\Postcode\InvalidPostcodeException;
use Brick\Postcode\PostcodeFormatter;
use Brick\Postcode\UnknownCountryException;
use Dibi\Exception;
use Drago\Attr\AttributeDetectionException;
use Drago\Commerce\Commerce;
use Drago\Commerce\Domain\Customer\Customer;
use Drago\Commerce\Event\CustomerUpdated;
use Drago\Commerce\Event\EventDispatcher;
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
		private readonly EventDispatcher $eventDispatcher,
	) {
	}


	/**
	 * Renders the customer form, pre-filling it with session data if available.
	 *
	 * @throws AttributeDetectionException
	 * @throws Exception
	 * @throws MoneyMismatchException
	 */
	public function render(): void
	{
		$this->prepareTemplate(__DIR__ . '/Customer.latte');
		$template = $this->template;
		$template->breadcrumbs = $this->getBreadcrumbs();
		$orderState = $this->orderSession->getItems();
		$template->carrier = $orderState->carrier;
		$template->payment = $orderState->payment;
		$this->applyCartTotals(
			$template,
			$this->shoppingCartSession->getTotals(),
			$this->orderSession->getCarrierPrice(),
			$this->orderSession->getPaymentPrice(),
		);

		if ($orderState->customer !== null) {
			$this->prefillForm('customer', (array) $orderState->customer);
		}

		$template->render();
	}


	/**
	 * @throws InvalidDatabaseException
	 */
	protected function createComponentCustomer(): BaseForm
	{
		$form = $this->customerFactory->addCustomer($this->translator);
		$form->onSuccess[] = $this->success(...);

		return $form;
	}


	/** @throws AbortException */
	public function success(Form $form, CustomerValues $data): void
	{
		try {
			$regionCode = $data->phone->getRegionCode();
			$postCode = ($this->commerce->getPostCodeOnRegionPhone() && $regionCode !== null)
				? (new PostcodeFormatter)->format($regionCode, $data->postal_code)
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

			$this->orderSession->setCustomer($customer);
			$this->eventDispatcher->dispatch(new CustomerUpdated($customer));
			$this->addRedirect($this->linkRedirectTarget);

		} catch (InvalidPostcodeException | UnknownCountryException $e) {
			$form->addError('The postal code does not match the same region as the phone number.');
		}
	}
}
