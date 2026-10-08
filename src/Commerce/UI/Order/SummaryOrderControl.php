<?php

declare(strict_types=1);

namespace Drago\Commerce\UI\Order;

use Brick\Money\Exception\MoneyMismatchException;
use Brick\Money\Exception\UnknownCurrencyException;
use Dibi\Exception;
use Drago\Attr\AttributeDetectionException;
use Drago\Commerce\Domain\Order\ItemUnavailableException;
use Drago\Commerce\Domain\Order\OrderException;
use Drago\Commerce\Domain\Order\OrderFingerprint;
use Drago\Commerce\Domain\Order\OutOfStockException;
use Drago\Commerce\Event\EventDispatcher;
use Drago\Commerce\Event\OrderPlaced;
use Drago\Commerce\Service\CheckoutPricing;
use Drago\Commerce\Service\DiscountCodeService;
use Drago\Commerce\Service\OrderService;
use Drago\Commerce\Service\OrderSession;
use Drago\Commerce\Service\ShoppingCartSession;
use Drago\Commerce\UI\BaseControl;
use Drago\Commerce\UI\BaseForm;
use Drago\Commerce\UI\Factory;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\HiddenField;
use Random\RandomException;
use Tracy\Debugger;


/**
 * @property-read SummaryOrderTemplate $template
 */
class SummaryOrderControl extends BaseControl
{
	public function __construct(
		private readonly ShoppingCartSession $shoppingCartSession,
		private readonly OrderSession $orderSession,
		private readonly OrderService $orderService,
		private readonly EventDispatcher $eventDispatcher,
		private readonly DiscountCodeService $discountCodeService,
		private readonly CheckoutPricing $checkoutPricing,
		private readonly Factory $factory,
	) {
	}


	/**
	 * @throws MoneyMismatchException
	 * @throws RandomException
	 */
	public function render(): void
	{
		$this->prepareTemplate(__DIR__ . '/Summary.latte');
		$template = $this->template;

		$cart = $this->shoppingCartSession->getTotals();
		$order = $this->orderSession->getItems();
		$this->applyCartTotals(
			$template,
			$cart,
			$this->orderSession->getCarrierPrice(),
			$this->orderSession->getPaymentPrice(),
		);

		// The form is bound to the order version displayed on this page.
		$this->orderSession->setOrderFingerprint(OrderFingerprint::create(
			$cart->items,
			$order,
			$cart->discountCode,
			$cart->subtotalPrice,
			$cart->discountAmount,
			$template->totalPrice,
		));

		$orderToken = bin2hex(random_bytes(32));
		$this->orderSession->setOrderToken($orderToken);

		$sendOrder = $this->getComponent('sendOrder');
		$tokenField = $sendOrder->getComponent('orderToken');
		if ($tokenField instanceof HiddenField) {
			$tokenField->setValue($orderToken);
		}

		$template->carrier = $order->carrier;
		$template->customer = $order->customer;
		$template->payment = $order->payment;
		$template->breadcrumbs = $this->getBreadcrumbs();
		$template->render();
	}


	/**
	 * @throws MoneyMismatchException
	 */
	protected function createComponentSendOrder(): BaseForm
	{
		$form = $this->factory->create($this->translator);
		$form->addHidden('orderToken');
		$form->addSubmit('send', 'Confirm the purchase');
		$form->onSuccess[] = $this->processOrder(...);
		return $form;
	}


	/**
	 * @throws AttributeDetectionException
	 * @throws Exception
	 * @throws MoneyMismatchException
	 * @throws UnknownCurrencyException
	 */
	public function processOrder(Form $form): void
	{
		// Prices kept in the session may be outdated; show the customer any change before charging it.
		$changed = $this->checkoutPricing->refreshCart();
		$deliveryChanged = $this->checkoutPricing->refreshDelivery();
		if ($changed !== [] || $deliveryChanged) {
			foreach ($changed as $name) {
				$form->addError($this->translate('The price of %s has changed, please review your order.', $name), false);
			}

			if ($deliveryChanged) {
				$form->addError($this->translate('The delivery or payment option has changed, please review your order.'), false);
			}

			return;
		}

		$order = $this->orderSession->getItems();
		$customer = $order->customer;
		$carrier = $order->carrier;
		$payment = $order->payment;

		if ($customer === null || $carrier === null || $payment === null) {
			$form->addError('Order details are incomplete.');
			return;
		}

		$cart = $this->shoppingCartSession->getTotals();
		if ($cart->items === []) {
			$form->addError('Your shopping cart is empty.');
			return;
		}

		$totalPrice = $cart->withExtras($carrier->price, $payment->price);

		// The submitted page version must still match the order as it is now.
		$tokenField = $form->getComponent('orderToken');
		$submittedToken = $tokenField instanceof HiddenField ? $tokenField->getValue() : null;
		$expectedToken = $this->orderSession->getOrderToken();
		$expectedFingerprint = $this->orderSession->getOrderFingerprint();
		$currentFingerprint = OrderFingerprint::create(
			$cart->items,
			$order,
			$cart->discountCode,
			$cart->subtotalPrice,
			$cart->discountAmount,
			$totalPrice,
		);

		if (
			!is_string($submittedToken)
			|| $expectedToken === null
			|| !hash_equals($expectedToken, $submittedToken)
			|| $expectedFingerprint === null
			|| !hash_equals($expectedFingerprint, $currentFingerprint)
		) {
			$form->addError('The order was changed or opened in another window, please review it and confirm again.');
			return;
		}

		try {
			$placement = $this->orderService->place(
				customer: $customer,
				carrier: $carrier,
				payment: $payment,
				items: $cart->items,
				subtotalPrice: $cart->subtotalPrice,
				discountAmount: $cart->discountAmount,
				totalPrice: $totalPrice,
				discountCode: $cart->discountCode,
			);
		} catch (OrderException $e) {
			$form->addError($this->describe($e), false);
			return;

		} catch (\Throwable $e) {
			Debugger::log($e, Debugger::EXCEPTION);
			$form->addError('An error occurred while processing your order. Please try again.');
			return;
		}

		try {
			$lang = $this->getPresenter()->getParameter('lang');
			$this->eventDispatcher->dispatch(
				new OrderPlaced(
					orderId: $placement->orderId,
					orderSummary: $placement->orderSummary,
					customer: $customer,
					carrier: $carrier,
					payment: $payment,
					shoppingCartSession: $this->shoppingCartSession,
					items: $cart->items,
					lang: is_string($lang) ? $lang : null,
				),
			);
		} catch (\Throwable $e) {
			Debugger::log($e, 'commerce-order-event');
		} finally {
			$this->shoppingCartSession->remove();
			$this->discountCodeService->remove();
			$this->orderSession->remove();
		}

		$this->addRedirect($this->linkRedirectTarget);
	}


	/**
	 * Builds the customer-facing message for an order that cannot be completed.
	 */
	private function describe(OrderException $e): string
	{
		return match (true) {
			$e instanceof OutOfStockException => $this->translate('The product %s is not in stock in the requested quantity.', $e->productName),
			$e instanceof ItemUnavailableException => $this->translate('The product %s is no longer available.', $e->productName),
			default => $this->translate($e->getMessage()),
		};
	}
}
