<?php

declare(strict_types=1);

namespace Drago\Commerce\Tests;

use Drago\Commerce\Domain\Checkout\CheckoutSteps;
use Tester\Assert;

require __DIR__ . '/bootstrap.php';

// 1. Test default steps
$defaultSteps = new CheckoutSteps;
Assert::same('default', $defaultSteps->products);
Assert::same('delivery', $defaultSteps->delivery);
Assert::same('customer', $defaultSteps->customer);
Assert::same('summary', $defaultSteps->summary);
Assert::same('shoppingCart', $defaultSteps->shoppingCart);
Assert::same('done', $defaultSteps->orderDone);

Assert::same([
	'shoppingCart' => 'Shopping Cart',
	'delivery' => 'Delivery',
	'customer' => 'Customer Info',
	'summary' => 'Summary',
], $defaultSteps->steps);

// 2. Test custom steps override
$customSteps = new CheckoutSteps([
	'products' => 'catalog',
	'delivery' => 'shipping',
	'customer' => 'billing',
	'summary' => 'overview',
	'shoppingCart' => 'cart',
	'orderDone' => 'finished',
]);

Assert::same('catalog', $customSteps->products);
Assert::same('shipping', $customSteps->delivery);
Assert::same('billing', $customSteps->customer);
Assert::same('overview', $customSteps->summary);
Assert::same('cart', $customSteps->shoppingCart);
Assert::same('finished', $customSteps->orderDone);

Assert::same([
	'cart' => 'Shopping Cart',
	'shipping' => 'Delivery',
	'billing' => 'Customer Info',
	'overview' => 'Summary',
], $customSteps->steps);
