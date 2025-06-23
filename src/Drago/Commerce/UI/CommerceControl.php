<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Commerce\UI;

use Drago\Commerce\UI\Order\CustomerControl;
use Drago\Commerce\UI\Order\DeliveryControl;
use Drago\Commerce\UI\Order\SummaryControl;
use Drago\Commerce\UI\Product\ProductControl;
use Drago\Commerce\UI\ShoppingCart\MiniCartControl;
use Drago\Commerce\UI\ShoppingCart\SummaryCartControl;


/**
 * Injects all main shop controls.
 */
trait CommerceControl
{
	public MiniCartControl $basketControl;
	public SummaryCartControl $shoppingCartControl;
	public DeliveryControl $deliveryControl;
	public CustomerControl $customerControl;
	public SummaryControl $summaryControl;
	public ProductControl $productControl;


	/**
	 * Inject all commerce controls.
	 */
	public function injectCommerceControl(
		MiniCartControl $basketControl,
		SummaryCartControl $shoppingCartControl,
		DeliveryControl $deliveryControl,
		CustomerControl $customerControl,
		SummaryControl $summaryControl,
		ProductControl $productControl,
	): void
	{
		$this->basketControl = $basketControl;
		$this->shoppingCartControl = $shoppingCartControl;
		$this->deliveryControl = $deliveryControl;
		$this->customerControl = $customerControl;
		$this->summaryControl = $summaryControl;
		$this->productControl = $productControl;
	}
}
