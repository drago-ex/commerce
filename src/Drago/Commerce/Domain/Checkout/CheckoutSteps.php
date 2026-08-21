<?php

declare(strict_types=1);

namespace Drago\Commerce\Domain\Checkout;


/**
 * Defines the checkout step identifiers and their display labels.
 * Supports custom step name overrides via the constructor.
 */
final class CheckoutSteps
{
	public string $products;
	public string $delivery;
	public string $customer;
	public string $summary;
	public string $shoppingCart;
	public string $orderDone;

	/** @var array<string, string> */
	public array $steps;


	/**
	 * @param array<string, mixed> $customSteps
	 */
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
