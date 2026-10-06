<?php

declare(strict_types=1);

namespace Drago\Commerce\UI\Order;

use Brick\Money\Exception\MoneyMismatchException;
use Brick\Money\Exception\UnknownCurrencyException;
use Dibi\Exception;
use Drago\Attr\AttributeDetectionException;
use Drago\Commerce\Domain\Order\ExpectedTotal;
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
	 * @throws AttributeDetectionException
	 * @throws Exception
	 * @throws RandomException
	 */
	public function render(): void
	{
		$template = $this->template;
		$template->setFile($this->templateControl ?: __DIR__ . '/Summary.latte');
		$template->setTranslator($this->translator);
		$this->prepareShoppingCartSummary(
			$template,
			$this->shoppingCartSession,
			$this->discountCodeService,
			$this->orderSession,
		);

		$expectedTotal = ExpectedTotal::format($template->totalPrice);
		$this->orderSession->setExpectedTotal($expectedTotal);
		$order = $this->orderSession->getItems();
		$discountCode = $this->discountCodeService->getCode();
		$this->orderSession->setOrderFingerprint(OrderFingerprint::create(
			$template->shoppingCart,
			$order,
			$discountCode,
			$template->subtotalPrice,
			$template->discountAmount,
			$template->totalPrice,
		));
		$orderToken = bin2hex(random_bytes(32));
		$this->orderSession->setOrderToken($orderToken);

		// Bind the form to the order version that was displayed on this page.
		$sendOrder = $this->getComponent('sendOrder');
		$totalField = $sendOrder->getComponent('expectedTotal');
		if ($totalField instanceof HiddenField) {
			$totalField->setValue($expectedTotal);
		}

		$tokenField = $sendOrder->getComponent('orderToken');
		if ($tokenField instanceof HiddenField) {
			$tokenField->setValue($orderToken);
		}

		$template->carrier = $this->getOrderItem('carrier');
		$template->customer = $this->getOrderItem('customer');
		$template->payment = $this->getOrderItem('payment');
		$template->breadcrumbs = $this->getBreadcrumbs();
		$template->render();
	}


	private function getOrderItem(string $name): mixed
	{
		$items = $this->orderSession->getItems();
		return $items->{$name} ?? null;
	}


	/**
	 * @throws MoneyMismatchException
	 */
	protected function createComponentSendOrder(): BaseForm
	{
		$form = $this->factory->create($this->translator);
		$form->addHidden('expectedTotal');
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

		$items = $this->shoppingCartSession->getItems();
		if ($items === []) {
			$form->addError('Your shopping cart is empty.');
			return;
		}

		$subtotalPrice = $this->shoppingCartSession->getSubtotalPrice();
		$discountCode = $this->discountCodeService->getCode();
		$discountAmount = $subtotalPrice->minus($this->shoppingCartSession->getTotalPrice($discountCode));
		$totalPrice = $this->calculateTotalPrice(
			$this->shoppingCartSession,
			$this->discountCodeService,
			$this->orderSession,
			$discountCode,
		);

		// Match the submitted page version and current order to the server-side snapshot.
		$totalField = $form->getComponent('expectedTotal');
		$tokenField = $form->getComponent('orderToken');
		$expectedFingerprint = $this->orderSession->getOrderFingerprint();
		$expectedToken = $this->orderSession->getOrderToken();
		$currentFingerprint = OrderFingerprint::create(
			$items,
			$order,
			$discountCode,
			$subtotalPrice,
			$discountAmount,
			$totalPrice,
		);
		if (
			!ExpectedTotal::matches($this->orderSession->getExpectedTotal(), $totalPrice)
			|| !$totalField instanceof HiddenField
			|| !ExpectedTotal::matches($totalField->getValue(), $totalPrice)
			|| !$tokenField instanceof HiddenField
			|| !is_string($tokenField->getValue())
			|| $expectedToken === null
			|| !hash_equals($expectedToken, $tokenField->getValue())
			|| $expectedFingerprint === null
			|| !hash_equals($expectedFingerprint, $currentFingerprint)
		) {
			$form->addError(
				$this->translate('The order total has changed to %s, please review your order.', $this->template->money($totalPrice)),
				false,
			);
			return;
		}

		try {
			$placement = $this->orderService->place(
				customer: $customer,
				carrier: $carrier,
				payment: $payment,
				items: $items,
				subtotalPrice: $subtotalPrice,
				discountAmount: $discountAmount,
				totalPrice: $totalPrice,
				discountCode: $discountCode,
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
			$this->eventDispatcher->dispatch(
				new OrderPlaced(
					orderId: $placement->orderId,
					orderSummary: $placement->orderSummary,
					customer: $customer,
					carrier: $carrier,
					payment: $payment,
					shoppingCartSession: $this->shoppingCartSession,
					items: $items,
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
