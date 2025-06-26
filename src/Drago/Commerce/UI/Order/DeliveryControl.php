<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Commerce\UI\Order;

use Brick\Money\Exception\UnknownCurrencyException;
use Dibi\Exception;
use Drago\Attr\AttributeDetectionException;
use Drago\Commerce\Domain\Delivery\CarrierMapper;
use Drago\Commerce\Domain\Delivery\CarrierRepository;
use Drago\Commerce\Domain\Delivery\PaymentMapper;
use Drago\Commerce\Domain\Delivery\PaymentRepository;
use Drago\Commerce\Service\OrderSession;
use Drago\Commerce\Service\ShoppingCartSession;
use Drago\Commerce\UI\BaseControl;
use Nette\Application\AbortException;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;


/**
 * @property-read DeliveryTemplate $template
 */
class DeliveryControl extends BaseControl
{
	public function __construct(
		private readonly ShoppingCartSession $shoppingCartSession,
		private readonly OrderSession $orderSession,
		private readonly CarrierRepository $carrierRepository,
		private readonly PaymentRepository $paymentRepository,
		private readonly CarrierMapper $carrierMapper,
		private readonly PaymentMapper $paymentMapper,
	) {
	}


	/**
	 * @throws AttributeDetectionException
	 * @throws Exception
	 * @throws UnknownCurrencyException
	 */
	public function render(): void
	{
		$template = $this->template;
		$template->setFile($this->templateControl ?: __DIR__ . '/Delivery.latte');
		$template->setTranslator($this->translator);
		$template->shoppingCart = $this->shoppingCartSession->getItems();
		$template->carrier = $this->carrierRepository->getCarrierItems();
		$template->payment = $this->paymentRepository->getPaymentItems();
		$template->breadcrumbs = $this->getBreadcrumbs();

		$delivery = $this->orderSession->getItems();
		if ($delivery->carrier !== null && $delivery->payment !== null) {
			$form = $this->getComponent('delivery');

			// Prefill form with data from session only if the form wasn't submitted yet.
			if (!$form->isSubmitted()) {

				$buttonSend = $this->getFormComponent($form, 'send');
				$buttonSend->setCaption('Update');

				$data = new DeliveryData();
				$data->carrierId = $delivery->carrier->id;
				$data->paymentId = $delivery->payment->id;
				$form->setDefaults($data);
			}
		}

		$template->render();
	}


	/**
	 * @throws AttributeDetectionException
	 */
	protected function createComponentDelivery(): Form
	{
		$form = new Form;

		$carrierItems = $this->carrierRepository->getOnlyIds();
		$form->addRadioList(DeliveryData::CarrierId, 'Carrier', $carrierItems)
			->setRequired('Please select a carrier.');

		$paymentItems = $this->paymentRepository->getOnlyIds();
		$form->addRadioList(DeliveryData::PaymentId, 'Payment', $paymentItems)
			->setRequired('Please select a payment method.');

		$form->addSubmit('send', 'Continue');
		$form->onSuccess[] = $this->success(...);
		return $form;
	}


	/**
	 * @throws AbortException
	 * @throws AttributeDetectionException
	 * @throws UnknownCurrencyException
	 * @throws Exception
	 * @throws BadRequestException
	 */
	public function success(Form $form, DeliveryData $data): void
	{
		$carrier = $this->carrierRepository->getOne($data->carrierId);
		$payment = $this->paymentRepository->getOne($data->paymentId);

		if (!$carrier || !$payment) {
			$this->error('Selected carrier or payment method not found.');
		}

		$this->orderSession->addItemCarrier($this->carrierMapper->map($carrier));
		$this->orderSession->addItemPayment($this->paymentMapper->map($payment));
		$this->getPresenter()->redirect($this->linkRedirectTarget);
	}
}
