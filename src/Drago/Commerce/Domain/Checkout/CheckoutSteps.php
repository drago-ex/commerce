<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace App\Commerce\Domain\Checkout;


/**
 * Defines route keys for each step in the checkout process.
 * Allows optional overriding of default step routes.
 */
final class CheckoutSteps
{
	/** Route for the product step (usually homepage) */
	public string $products;

	/** Route for the delivery step */
	public string $delivery;

	/** Route for the customer step */
	public string $customer;

	/** Route for the summary step */
	public string $summary;

	/** Route for the shopping cart step */
	public string $shoppingCart;

	/** Route for the order confirmation step */
	public string $orderDone;

	/** List of visible steps in the checkout */
	public array $steps;


	public function __construct(array $customSteps = [])
	{
		$this->products = 'default';
		$this->delivery = 'delivery';
		$this->customer = 'customer';
		$this->summary = 'summary';
		$this->shoppingCart = 'shoppingCart';
		$this->orderDone = 'done';

		$this->steps = [
			$this->shoppingCart => 'Shopping Cart',
			$this->delivery => 'Delivery',
			$this->customer => 'Customer Info',
			$this->summary => 'Summary',
		];

		foreach ($customSteps as $key => $value) {
			if (property_exists($this, $key)) {
				$this->$key = $value;
			}
		}
	}
}
