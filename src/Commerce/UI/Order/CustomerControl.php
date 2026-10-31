<?php declare(strict_types=1);

namespace Drago\Commerce\UI\Order;

use Brick\Math\Exception\MathException;
use Brick\Money\Exception\MoneyMismatchException;
use Brick\Postcode\Exception\InvalidPostcodeException;
use Brick\Postcode\Exception\UnknownCountryException;
use Brick\Postcode\PostcodeFormatter;
use Dibi\Exception;
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
use function is_string;


/** @property-read CustomerTemplate $template */
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
	 * @throws Exception
	 * @throws MoneyMismatchException
	 * @throws MathException
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


	/** @throws AbortException */
	public function processCustomerDetailsForm(Form $form, CustomerValues $data): void
	{
		$postCode = $data->postalCode;
		if ($this->commerce->getValidatePostCode()) {
			try {
				$postCode = (new PostcodeFormatter)->format($data->country, $data->postalCode);
			} catch (InvalidPostcodeException) {
				$form->addError('The postal code is not valid for the chosen country.');
				return;
			} catch (UnknownCountryException) {
				// brick/postcode does not know the format of the country, so the postal code stays as written
			}
		}

		$customer = new Customer(
			email: $data->email,
			phone: $data->phone,
			name: $data->name,
			surname: $data->surname,
			street: $data->street,
			city: $data->city,
			postalCode: $postCode,
			country: $data->country,
			note: $data->note,
		);

		$this->orderSession->setCustomer($customer);
		$this->eventDispatcher->dispatch(new CustomerUpdated($customer));
		$this->addRedirect($this->linkRedirectTarget);
	}


	/** @throws InvalidDatabaseException */
	protected function createComponentCustomer(): BaseForm
	{
		$lang = $this->getPresenter()->getParameter('lang');
		$form = $this->customerFactory->addCustomer($this->translator, is_string($lang) ? $lang : null);
		$form->onSuccess[] = $this->processCustomerDetailsForm(...);

		return $form;
	}
}
