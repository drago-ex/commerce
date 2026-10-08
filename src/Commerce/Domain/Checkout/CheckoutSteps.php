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
	 * Full step sequence, including orderDone (unlike $steps, which only
	 * holds the labeled steps shown in the breadcrumbs). Built from the
	 * properties above, so a renamed step (via $customSteps) is reflected
	 * here automatically — this never needs editing on its own.
	 *
	 * @var list<string>
	 */
	private array $flow;


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

		foreach ($customSteps as $key => $value) {
			if (property_exists($this, $key)) {
				$this->$key = $value;
			}
		}

		$this->steps = [
			$this->shoppingCart => 'Shopping Cart',
			$this->delivery => 'Delivery',
			$this->customer => 'Customer Info',
			$this->summary => 'Summary',
		];

		$this->flow = [
			$this->shoppingCart,
			$this->delivery,
			$this->customer,
			$this->summary,
			$this->orderDone,
		];
	}


	/**
	 * Returns the step that follows the given one in the checkout flow,
	 * or null when $step is the last one (or not recognized).
	 */
	public function next(string $step): ?string
	{
		$index = array_search($step, $this->flow, true);
		return $index !== false ? ($this->flow[$index + 1] ?? null) : null;
	}
}
