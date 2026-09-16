# Drago Commerce (development version)

Simple shopping cart.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://github.com/drago-ex/commerce/blob/main/license)
[![PHP version](https://badge.fury.io/ph/drago-ex%2Fcommerce.svg)](https://badge.fury.io/ph/drago-ex%2Fcommerce)
[![Tests](https://github.com/drago-ex/commerce/actions/workflows/tests.yml/badge.svg)](https://github.com/drago-ex/commerce/actions/workflows/tests.yml)
[![Coding Style](https://github.com/drago-ex/commerce/actions/workflows/coding-style.yml/badge.svg)](https://github.com/drago-ex/commerce/actions/workflows/coding-style.yml)

## Requirements
- PHP >= 8.3
- Nette Framework
- Composer

## Installation
```bash
composer require drago-ex/commerce
```

## Frontend Assets
Add the Composer package as a local npm dependency:

```json
{
	"type": "module",
	"dependencies": {
		"drago-commerce": "file:vendor/drago-ex/commerce"
	}
}
```

Install JavaScript dependencies:

```bash
npm install
```

Import the Commerce behavior and styles in your Vite entry point:

```js
import naja from 'naja';
import Commerce from 'drago-commerce';
import 'drago-commerce/styles';

naja.initialize();
new Commerce().initialize(naja);
```

The default integration submits cart quantity changes through Naja and shows a loading spinner during AJAX requests.

## Extension Registration
In your `config.neon` file, register the extension:
```neon
extensions:
    - Nepada\Bridges\PhoneNumberInputDI\PhoneNumberInputExtension
    commerce: Drago\Commerce\DI\CommerceExtension
```

## Configure Commerce Settings
Still in `config.neon`, configure the basic commerce settings:
```neon
commerce:
    currency: CZK
    moneyFormat: cs_CZ
    moneySymbol: ''
    moneyFractionDigits: 0
    defaultRegionCode: ['autoDetect', 'CZ']
    allowedRegionPhoneNumber: CZ
    postCodeOnRegionPhone: true
    # geoLite2Path: %appDir%/../data/GeoLite2-City.mmdb
```

## Use Commerce Trait in Your Presenter
Add the `CommerceControl` trait to your presenter for easy integration of commerce components:
```php
use Drago\Commerce\UI\CommerceControl;

class CommercePresenter extends Nette\Application\UI\Presenter
{
    use CommerceControl;

    // other code
}
```

The trait injects the six commerce controls (`$this->deliveryControl`, `$this->customerControl`, `$this->summaryOrderControl`, `$this->shoppingCartControl`, `$this->miniCartControl`, `$this->productControl`) and configures each checkout-step control for its place in the flow: the step map, completed steps, current step, and the redirect target for the next step. Each control always represents the same fixed step — a `DeliveryControl` is always the delivery step — so this configuration doesn't need repeating per presenter; a presenter only sets what genuinely varies per use, such as the translator. See "Example Presenter" below.

## Inject CheckoutProcess Service
```php
public function __construct(
    private readonly CheckoutProcess $checkoutProcess
) {
    parent::__construct();
}
```

`CheckoutProcess` is used directly in the presenter for the `startup()` guard shown below (see "Example Presenter").

## Example Presenter

A presenter using the full checkout flow looks like this:

```php
<?php

declare(strict_types=1);

namespace App\Presentation\Front\Home;

use App\Presentation\BasePresenter;
use Drago\Commerce\Domain\Checkout\CheckoutProcess;
use Drago\Commerce\UI\CommerceControl;
use Drago\Commerce\UI\Order\CustomerControl;
use Drago\Commerce\UI\Order\DeliveryControl;
use Drago\Commerce\UI\Order\SummaryOrderControl;
use Drago\Commerce\UI\Product\ProductControl;
use Drago\Commerce\UI\ShoppingCart\MiniCartControl;
use Drago\Commerce\UI\ShoppingCart\SummaryCartControl;

/** @property-read HomeTemplate $template */
final class CommercePresenter extends BasePresenter
{
	use CommerceControl;

	public function __construct(
		protected CheckoutProcess $checkoutProcess,
	) {
		parent::__construct();
	}


	// Guards every action in this presenter against being opened directly
	// (e.g. from a bookmark or back button) when its prerequisites aren't
	// met. startup() runs before every action, so one override covers the
	// whole flow.
	public function startup(): void
	{
		parent::startup();
		$target = $this->checkoutProcess->getRedirectTargetForAction($this->getAction());
		if ($target !== null && $target !== $this->getAction()) {
			$this->redirect($target);
		}
	}


	// Cart icon/count shown in the layout (e.g. navbar).
	protected function createComponentMiniCart(): MiniCartControl
	{
		$control = $this->miniCartControl;
		$control->translator = $this->getTranslator();
		return $control;
	}


	// Product listing — the entry point of the flow.
	protected function createComponentProduct(): ProductControl
	{
		return $this->productControl;
	}


	// Cart contents + discount code form.
	protected function createComponentShoppingCart(): SummaryCartControl
	{
		$control = $this->shoppingCartControl;
		$control->translator = $this->getTranslator();
		return $control;
	}


	// Carrier + payment method selection.
	protected function createComponentDelivery(): DeliveryControl
	{
		$control = $this->deliveryControl;
		$control->translator = $this->getTranslator();
		return $control;
	}


	// Contact + billing form.
	protected function createComponentCustomer(): CustomerControl
	{
		$control = $this->customerControl;
		$control->translator = $this->getTranslator();
		return $control;
	}


	// Final review + "place order" button.
	protected function createComponentSummaryOrder(): SummaryOrderControl
	{
		$control = $this->summaryOrderControl;
		$control->translator = $this->getTranslator();
		return $control;
	}
}
```

`translator` is optional — set it if you're using `drago-ex/translator` (or your own `Translator` implementation) so the bundled templates render translated labels instead of the English defaults.

### How a checkout-step control gets configured

`CommerceControl::injectCommerceControl()` configures each checkout-step control via `CheckoutProcess::configureStep()`:

```php
public function configureStep(BaseControl $control, string $step): BaseControl
{
	$control->setSteps($this->getSteps());
	$control->setCompletedSteps($this->getCompletedSteps());
	$control->setCurrentStep($step);

	if ($next = $this->getNextStep($step)) {
		$control->setLinkRedirectTarget($next);
	}

	return $control;
}
```

`getNextStep()` reads the checkout flow's order from `CheckoutSteps`, so renaming a step via the `customSteps` constructor argument (see "Customize Checkout Steps" below) is reflected automatically.

This runs in `injectCommerceControl()`, which Nette calls right after the presenter is constructed, before `startup()`. `getCompletedSteps()` only reads session state (cart contents, the in-progress order) and never the current action, so this is safe regardless of which action is being handled.

If a control needs to represent a different step than its default (e.g. reusing `DeliveryControl` in a custom flow), call `$this->checkoutProcess->configureStep($this->deliveryControl, 'yourStep')` in your own presenter — it's a plain method call, safe to call again.

### Why the `startup()` guard exists

`CheckoutProcess::getRedirectTargetForAction()` checks the actual session state (cart contents, chosen carrier, filled-in customer) against the step being requested, and returns where to send the visitor instead — e.g. someone opening `/customer` directly with an empty cart gets redirected back to the product listing rather than seeing a broken form.

Calling it from `startup()` covers every action in the presenter with one override: for any action other than `delivery`/`customer`/`summary`, the resolver's `match` falls through to `default => null` — a safe no-op — so nothing needs to be added for other actions such as the product listing or the shopping cart.

## Optional Custom Template
Each control/component has a public property called `templateControl` that lets you specify a custom template file for rendering. Use this if you want to customize the look or layout of the component.

Here's a simple example showing how to set a custom template in the component factory method:
```php
protected function createComponentDelivery(): DeliveryControl
{
	$control = $this->deliveryControl;

	// Optional: override the default template file
	$control->templateControl = __DIR__ . '/templates/Delivery/customTemplate.latte';

	// Additional setup like steps, current step, etc.
	$control->setSteps($this->checkoutProcess->getSteps());
	// ...

	return $control;
}
```

## Latte Templates

### 1. Layout (`@layout.latte`)
Include the mini cart widget in your navbar / header:

```latte
{snippet cart}
    {control miniCart}
{/snippet}
```

### 2. Product Catalog (`default.latte`)
```latte
{block content}
    {control product}
{/block}
```

### 3. Shopping Cart (`shoppingCart.latte`)
```latte
{block content}
    {snippet shoppingCart}
        {control shoppingCart}
    {/snippet}
{/block}
```

### 4. Shipping & Payment (`delivery.latte`)
```latte
{block content}
    {snippet delivery}
        {control delivery}
    {/snippet}
{/block}
```

### 5. Customer Details (`customer.latte`)
```latte
{block content}
    {control customer}
{/block}
```

### 6. Order Summary (`summary.latte`)
```latte
{block content}
    {snippet summaryOrder}
        {control summaryOrder}
    {/snippet}
{/block}
```

### 7. Order Confirmation (`done.latte`)
```latte
{block content}
    <h1>{_'Order completed'}</h1>
    <p class="alert alert-success">{_'Thank you, your order has been successfully submitted.'}</p>
    <a n:href="default" class="btn btn-primary">{_'Back to the menu'}</a>
{/block}
```

## Register Services
Register the checkout services so Nette DI can create and wire the checkout flow.

The minimal registration below is enough when you keep the default step names and templates; Nette will autowire required dependencies (ShoppingCartSession, OrderSession) into CheckoutProcess.

```neon
services:
    - Drago\Commerce\Domain\Checkout\CheckoutProcess
    - Drago\Commerce\Domain\Checkout\CheckoutSteps
```

If you want to override step names or provide a custom CheckoutSteps instance (for localization, branding, or per-step template mapping), use the explicit service configuration shown in the "Customize Checkout Steps (Optional)" section.

## Customize Checkout Steps (Optional)
If you want to rename the default checkout steps or add custom ones, you can configure your own instance of `CheckoutSteps` via the service container and pass it to `CheckoutProcess`. This gives you full control over step naming (e.g. for localization, branding, or structural changes).

Example configuration in `neon`:
```neon
services:
	# Register CheckoutSteps with custom step keys
	checkoutSteps:
		factory: Drago\Commerce\Domain\Checkout\CheckoutSteps
		arguments:
			-  # Custom step names (you can omit or override only selected ones)
				products: 'products'
				delivery: 'shipping'
				customer: 'billing'
				summary: 'summary'
				shoppingCart: 'shoppingCart'
				orderDone: 'done'

	# Register CheckoutProcess with dependencies injected
	checkoutProcess:
		factory: Drago\Commerce\Domain\Checkout\CheckoutProcess
		arguments:
			- @Drago\Commerce\Service\ShoppingCartSession
			- @Drago\Commerce\Service\OrderSession
			- @checkoutSteps
```

## Summary
This way you have a fully configured commerce module ready for extension and use in your Nette application.
