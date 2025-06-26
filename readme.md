# Drago Commerce
Simple shopping cart.

## Requirements
- PHP 8.3 or higher
- composer

## Installation
```bash
composer require drago-ex/commerce
```

## Register the Extension
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
```

## Register Services
Register the core services in `config.neon`:
```neon
services:
    - Drago\Commerce\Domain\Checkout\CheckoutProcess
    - Drago\Commerce\Domain\Checkout\CheckoutSteps
```

## Use Commerce Trait in Your Presenter
Add the `CommerceControl` trait to your presenter for easy integration of commerce components:
```php
use Drago\Commerce\UI\CommerceControl;

class CheckoutPresenter extends Nette\Application\UI\Presenter
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

##  Setup Shopping Cart & Checkout Components
```php
protected function createComponentDelivery(): DeliveryControl
{
    $control = $this->deliveryControl;
    $control->setSteps($this->checkoutProcess->getSteps());
    $control->setCompletedSteps($this->checkoutProcess->getCompletedSteps());
    $control->setCurrentStep($this->checkoutProcess->steps()->delivery);
    $control->setLinkRedirectTarget($this->checkoutProcess->steps()->customer);
    return $control;
}

// same pattern for other createComponent* methods (Customer, Summary, ShoppingCart, MiniCart)
```

## Optional Custom Template
Each control/component has a public property called `templateControl` that lets you specify a custom template file for rendering. Use this if you want to customize the look or layout of the component.

Here’s a simple example showing how to set a custom template in the component factory method:
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

## Handle Redirects in Actions
```php
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
```

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
