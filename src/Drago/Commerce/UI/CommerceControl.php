<?php

declare(strict_types=1);

namespace Drago\Commerce\UI;

use Drago\Commerce\Domain\Checkout\CheckoutProcess;
use Drago\Commerce\UI\Order\CustomerControl;
use Drago\Commerce\UI\Order\DeliveryControl;
use Drago\Commerce\UI\Order\SummaryOrderControl;
use Drago\Commerce\UI\Product\ProductControl;
use Drago\Commerce\UI\ShoppingCart\MiniCartControl;
use Drago\Commerce\UI\ShoppingCart\SummaryCartControl;


/**
 * Injects all main shop controls and wires each checkout-step control to
 * its fixed place in the flow (steps map, completed steps, current step,
 * and the redirect target for the next step).
 *
 * This mapping is structural — a DeliveryControl always represents the
 * delivery step, never anything else — so it's configured once here
 * instead of being repeated in every presenter's createComponent*()
 * methods. A presenter only needs to set anything it genuinely varies per
 * use (e.g. the translator, or a custom templateControl).
 */
trait CommerceControl
{
	public MiniCartControl $miniCartControl;
	public SummaryCartControl $shoppingCartControl;
	public DeliveryControl $deliveryControl;
	public CustomerControl $customerControl;
	public SummaryOrderControl $summaryOrderControl;
	public ProductControl $productControl;


	/**
	 * Inject all commerce controls and configure the checkout ones for
	 * their step in the flow.
	 */
	public function injectCommerceControl(
		MiniCartControl $miniCartControl,
		SummaryCartControl $shoppingCartControl,
		DeliveryControl $deliveryControl,
		CustomerControl $customerControl,
		SummaryOrderControl $summaryOrderControl,
		ProductControl $productControl,
		CheckoutProcess $checkoutProcess,
	): void
	{
		$this->miniCartControl = $miniCartControl;
		$this->shoppingCartControl = $shoppingCartControl;
		$this->deliveryControl = $deliveryControl;
		$this->customerControl = $customerControl;
		$this->summaryOrderControl = $summaryOrderControl;
		$this->productControl = $productControl;

		// MiniCart lives outside the step flow (typically in the layout),
		// so it only needs somewhere to send the customer when clicked.
		$miniCartControl->setLinkRedirectTarget($checkoutProcess->steps()->shoppingCart);

		// Each of these always represents the same step, so the mapping
		// is hardcoded here rather than derived — there's nothing to
		// configure differently per presenter.
		$checkoutProcess->configureStep($shoppingCartControl, $checkoutProcess->steps()->shoppingCart);
		$checkoutProcess->configureStep($deliveryControl, $checkoutProcess->steps()->delivery);
		$checkoutProcess->configureStep($customerControl, $checkoutProcess->steps()->customer);
		$checkoutProcess->configureStep($summaryOrderControl, $checkoutProcess->steps()->summary);
	}
}
