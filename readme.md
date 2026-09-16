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

## Inject CheckoutProcess Service
```php
public function __construct(
    private readonly CheckoutProcess $checkoutProcess
) {
    parent::__construct();
}
```

## Example Presenter

`CommerceControl` injects one property per component (`$this->deliveryControl`,
`$this->customerControl`, …) — the presenter's job is just to configure each one
with the current checkout state before returning it. This is a complete presenter
covering the whole flow (`Product` → `SummaryCart` → `Delivery` → `Customer` →
`SummaryOrder`):

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


final class CommercePresenter extends BasePresenter
{
	use CommerceControl;

	public function __construct(
		protected CheckoutProcess $checkoutProcess,
	) {
		parent::__construct();
	}

	// Cart icon/count shown in the layout (e.g. navbar), independent of
	// the current step — that's why it doesn't call setSteps()/setCurrentStep().
	protected function createComponentMiniCart(): MiniCartControl
	{
		$control = $this->miniCartControl;
		$control->setLinkRedirectTarget($this->checkoutProcess->steps()->shoppingCart);
		$control->translator = $this->getTranslator();
		return $control;
	}

	// Product listing. No checkout state needed — this is where the flow starts.
	protected function createComponentProduct(): ProductControl
	{
		return $this->productControl;
	}

	// Cart contents + discount code form. setLinkRedirectTarget() points to
	// the *next* step (delivery) — every step control needs this so its
	// "Continue" button/redirect knows where to go.
	protected function createComponentShoppingCart(): SummaryCartControl
	{
		$control = $this->shoppingCartControl;
		$control->setSteps($this->checkoutProcess->getSteps());
		$control->setCompletedSteps($this->checkoutProcess->getCompletedSteps());
		$control->setCurrentStep($this->checkoutProcess->steps()->shoppingCart);
		$control->setLinkRedirectTarget($this->checkoutProcess->steps()->delivery);
		$control->translator = $this->getTranslator();
		return $control;
	}

	// Carrier + payment method selection. Same four calls as above, just
	// one step further along — setCurrentStep() is what highlights this
	// step in the breadcrumbs, setLinkRedirectTarget() points to Customer.
	protected function createComponentDelivery(): DeliveryControl
	{
		$control = $this->deliveryControl;
		$control->setSteps($this->checkoutProcess->getSteps());
		$control->setCompletedSteps($this->checkoutProcess->getCompletedSteps());
		$control->setCurrentStep($this->checkoutProcess->steps()->delivery);
		$control->setLinkRedirectTarget($this->checkoutProcess->steps()->customer);
		$control->translator = $this->getTranslator();
		return $control;
	}

	// Contact + billing form.
	protected function createComponentCustomer(): CustomerControl
	{
		$control = $this->customerControl;
		$control->setSteps($this->checkoutProcess->getSteps());
		$control->setCompletedSteps($this->checkoutProcess->getCompletedSteps());
		$control->setCurrentStep($this->checkoutProcess->steps()->customer);
		$control->setLinkRedirectTarget($this->checkoutProcess->steps()->summary);
		$control->translator = $this->getTranslator();
		return $control;
	}

	// Final review + "place order" button. Redirects to orderDone on success.
	protected function createComponentSummaryOrder(): SummaryOrderControl
	{
		$control = $this->summaryOrderControl;
		$control->setSteps($this->checkoutProcess->getSteps());
		$control->setCompletedSteps($this->checkoutProcess->getCompletedSteps());
		$control->setCurrentStep($this->checkoutProcess->steps()->summary);
		$control->setLinkRedirectTarget($this->checkoutProcess->steps()->orderDone);
		$control->translator = $this->getTranslator();
		return $control;
	}

	// Guards every step action (see below) against being opened directly
	// (e.g. from a bookmark or back button) when its prerequisites aren't met.
	private function redirectIfNecessary(): void
	{
		$target = $this->checkoutProcess->getRedirectTargetForAction($this->getAction());
		if ($target !== null && $target !== $this->getAction()) {
			$this->redirect($target);
		}
	}

	public function actionDelivery(): void
	{
		$this->redirectIfNecessary();
	}

	public function actionCustomer(): void
	{
		$this->redirectIfNecessary();
	}

	public function actionSummary(): void
	{
		$this->redirectIfNecessary();
	}
}
```

### What each part actually does

| Method | Purpose |
|---|---|
| `setSteps()` | Gives the control the full step map (`CheckoutSteps::$steps`) so it can render the breadcrumbs. |
| `setCompletedSteps()` | List of steps already finished — the breadcrumbs mark these as done/clickable instead of upcoming. |
| `setCurrentStep()` | Which step to highlight as active right now. |
| `setLinkRedirectTarget()` | Where the control should send the customer after a successful action on *this* step — i.e. the next step in the flow. Required; throws if left empty. |
| `translator` | Optional — set it if you're using `drago-ex/translator` (or your own `Translator` implementation) so the bundled templates render translated labels instead of the English defaults. |

`createComponentMiniCart()` and `createComponentProduct()` are the two exceptions:
the mini-cart is shown outside the step flow (typically in the layout), so it only
needs a redirect target, not the full step/breadcrumb setup; the product listing
is the entry point, so it needs neither.

### Why `redirectIfNecessary()` exists

`CheckoutProcess::getRedirectTargetForAction()` checks the *actual* session state
(cart contents, chosen carrier, filled-in customer) against the step being
requested, and returns where to send the visitor instead — e.g. someone opening
`/customer` directly with an empty cart gets redirected back to the product
listing rather than seeing a broken form. `actionDelivery()`/`actionCustomer()`/
`actionSummary()` just need to call it; `actionDefault()` (products) and
`actionShoppingCart()` don't, since there's nothing before them to violate.

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
