<?php

declare(strict_types=1);

namespace Drago\Commerce\UI\Order;

use Brick\Money\Exception\MoneyMismatchException;
use Brick\Money\Exception\UnknownCurrencyException;
use Dibi\Exception;
use Drago\Attr\AttributeDetectionException;
use Drago\Commerce\Domain\Delivery\CarrierMapper;
use Drago\Commerce\Domain\Delivery\CarrierRepository;
use Drago\Commerce\Domain\Delivery\PaymentMapper;
use Drago\Commerce\Domain\Delivery\PaymentRepository;
use Drago\Commerce\Event\DeliveryOptionsChanged;
use Drago\Commerce\Event\EventDispatcher;
use Drago\Commerce\Service\OrderSession;
use Drago\Commerce\Service\ShoppingCartSession;
use Drago\Commerce\UI\BaseControl;
use Drago\Commerce\UI\BaseForm;
use Drago\Commerce\UI\Factory;
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
		private readonly EventDispatcher $eventDispatcher,
		private readonly Factory $factory,
	) {
	}


	/**
	 * @throws AttributeDetectionException
	 * @throws Exception
	 * @throws UnknownCurrencyException
	 * @throws MoneyMismatchException
	 */
	public function render(): void
	{
		$this->prepareTemplate(__DIR__ . '/Delivery.latte');
		$template = $this->template;
		$template->carrier = $this->carrierRepository->getCarrierItems();
		$template->payment = $this->paymentRepository->getPaymentItems();
		$template->breadcrumbs = $this->getBreadcrumbs();
		$delivery = $this->orderSession->getItems();
		$template->selectedCarrier = $delivery->carrier;
		$template->selectedPayment = $delivery->payment;

		$this->applyCartTotals(
			$template,
			$this->shoppingCartSession->getTotals(),
			$this->orderSession->getCarrierPrice(),
			$this->orderSession->getPaymentPrice(),
		);

		if ($delivery->carrier !== null && $delivery->payment !== null) {
			$data = new DeliveryValues;
			$data->carrierId = $delivery->carrier->id;
			$data->paymentId = $delivery->payment->id;
			$this->prefillForm('delivery', $data);
		}

		$template->render();
	}


	/**
	 * @throws AttributeDetectionException
	 */
	protected function createComponentDelivery(): BaseForm
	{
		$form = $this->factory->create($this->translator);
		$carrierItems = $this->carrierRepository->getOnlyIds();
		$form->addRadioList(DeliveryValues::CarrierId, 'Carrier', $carrierItems)
			->setRequired('Please select a carrier.');

		$paymentItems = $this->paymentRepository->getOnlyIds();
		$form->addRadioList(DeliveryValues::PaymentId, 'Payment', $paymentItems)
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
	public function success(Form $form, DeliveryValues $data): void
	{
		$carrierEntity = $this->carrierRepository->getOne($data->carrierId);
		$paymentEntity = $this->paymentRepository->getOne($data->paymentId);

		if (!$carrierEntity || !$paymentEntity) {
			$this->error('Selected carrier or payment method not found.');
		}

		$carrier = $this->carrierMapper->map($carrierEntity);
		$payment = $this->paymentMapper->map($paymentEntity);

		$this->orderSession->setCarrier($carrier);
		$this->orderSession->setPayment($payment);

		$this->eventDispatcher->dispatch(new DeliveryOptionsChanged($carrier, $payment));
		$this->addRedirect($this->linkRedirectTarget);
	}
}
