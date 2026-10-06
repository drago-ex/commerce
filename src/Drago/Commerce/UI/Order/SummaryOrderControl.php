<?php

declare(strict_types=1);

namespace Drago\Commerce\UI\Order;

use Brick\Money\Exception\MoneyMismatchException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Dibi\Exception;
use Drago\Attr\AttributeDetectionException;
use Drago\Commerce\Domain\DiscountCode\DiscountCodeEntity;
use Drago\Commerce\Domain\Order\ItemUnavailableException;
use Drago\Commerce\Domain\Order\OrderException;
use Drago\Commerce\Domain\Order\OutOfStockException;
use Drago\Commerce\Event\EventDispatcher;
use Drago\Commerce\Event\OrderPlaced;
use Drago\Commerce\Service\CheckoutPricing;
use Drago\Commerce\Service\DiscountCodeService;
use Drago\Commerce\Service\OrderService;
use Drago\Commerce\Service\OrderSession;
use Drago\Commerce\Service\ShoppingCartSession;
use Drago\Commerce\UI\BaseControl;
use Nette\Application\UI\Form;
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
	) {
	}


	/**
	 * @throws MoneyMismatchException
	 * @throws AttributeDetectionException
	 * @throws Exception
	 */
	public function render(): void
	{
		$template = $this->template;
		$template->setFile($this->templateControl ?: __DIR__ . '/Summary.latte');
		$template->setTranslator($this->translator);
		$template->shoppingCart = $this->shoppingCartSession->getItems();
		$template->amountItems = $this->shoppingCartSession->getAmountItems();
		$template->originalPrice = $this->shoppingCartSession->getOriginalPrice();
		$template->subtotalPrice = $this->shoppingCartSession->getSubtotalPrice();
		$template->productDiscountAmount = $template->originalPrice->minus($template->subtotalPrice);
		$discountCode = $this->discountCodeService->getCode();
		$template->discountAmount = $template->subtotalPrice->minus($this->shoppingCartSession->getTotalPrice($discountCode));
		$template->discountCode = $discountCode?->code;
		$template->totalPrice = $this->getTotalPrice($discountCode);
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
	private function getTotalPrice(?DiscountCodeEntity $discountCode = null): Money
	{
		return $this->shoppingCartSession->getTotalPrice($discountCode)
			->plus($this->orderSession->getCarrierPrice())
			->plus($this->orderSession->getPaymentPrice());
	}


	protected function createComponentSendOrder(): Form
	{
		$form = new Form;
		$form->setTranslator($this->translator);
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
		$totalPrice = $this->getTotalPrice($discountCode);

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

		$this->getPresenter()->redirect($this->linkRedirectTarget);
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
