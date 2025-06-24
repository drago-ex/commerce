# Drago Commerce
Simple shopping cart.

## Requirements
- PHP 8.3 or higher
- composer

## Installation
```
composer require drago-ex/commerce
```

## Extension registration
```neon
extensions:
	- Nepada\Bridges\PhoneNumberInputDI\PhoneNumberInputExtension
	commerce: Drago\Commerce\DI\CommerceExtension

commerce:

	# Currency selection, ISO currency code or ISO numeric currency code.
	currency: CZK

	# Formats this Money to the given locale.
	moneyFormat: cs_CZ

	# If you do not require the customer to always enter the international telephone prefix for the order,
	# enter the default value according to ISO 3166-1 alpha-2 country codes.
	# We can also use automatic detection with a set default value in case automatic detection fails.
	# In this case we will use the field, the first value must be "autoDetect" the second value according to county codes.
	defaultRegionCode: ['autoDetect', 'CZ']

	# Restricted to specific international telephone numbers only.
	# Specify one or more ISO 3166-1 alpha-2 country codes.
	allowedRegionPhoneNumber: CZ

	# Request a postal code of the same region as your phone number.
	postCodeOnRegionPhone: true
```

## Usage
We insert a trait with components in the presenter.
```php
use CommerceControl;
```

## Setting the unique template names that the commerce will use.
```php
private const string
	PageProducts = 'products',
	PageDelivery = 'delivery',
	PageCustomer = 'customer',
	PageSummary = 'summary',
	PageShoppingCart = 'shoppingCart',
	PageOrderDone = 'done';
```

## We will set up the commerce components.
```php
protected function createComponentProducts(): ProductControl
{
	return $this->productControl;
}


protected function createComponentDelivery(): DeliveryControl
{
	$control = $this->deliveryControl;
	$control->setLinkRedirectTarget(self::PageCustomer);
	return $control;
}


protected function createComponentCustomer(): CustomerControl
{
	$control = $this->customerControl;
	$control->setLinkRedirectTarget(self::PageSummary);
	return $control;
}


protected function createComponentSummary(): SummaryControl
{
	$control = $this->summaryControl;
	$control->setLinkRedirectTarget(self::PageOrderDone);
	return $control;
}


protected function createComponentShoppingCart(): SummaryCartControl
{
	$control = $this->shoppingCartControl;
	$control->setLinkRedirectTarget(self::PageDelivery);
	return $control;
}


protected function createComponentMiniCart(): MiniCartControl
{
	$control = $this->miniCartControl;
	$control->setLinkRedirectTarget(self::PageShoppingCart);
	return $control;
}
```

## Handling redirects if the user arrives at a page that is not current.
```php
/**
 * Checks if the shopping cart contains any items.
 */
private function hasItems(): bool
{
	return count($this->shoppingCart->getItems()) > 0;
}


/**
 * Retrieves the current order draft.
 */
private function getOrderDraft(): OrderDraft
{
	return $this->customerOrder->getItems();
}


/**
 * Handles the 'delivery' action.
 * Redirects to the products page if the cart is empty and current action is not 'products'.
 */
public function actionDelivery(): void
{
	if (!$this->hasItems() && $this->getAction() !== self::PageProducts) {
		$this->redirect(self::PageProducts);
	}
}


/**
 * Handles the 'customer' action.
 * Redirects based on whether carrier is selected and if the cart has items:
 * If no carrier but cart has items, redirects to 'delivery'.
 * If no carrier and cart is empty, redirect to 'products'.
 */
public function actionCustomer(): void
{
	$draft = $this->getOrderDraft();
	$hasItems = $this->hasItems();
	$carrier = $draft->carrier;

	$redirectTarget = match (true) {
		$carrier === null && $hasItems => self::PageDelivery,
		$carrier === null && !$hasItems => self::PageProducts,
		default => null,
	};

	if ($redirectTarget !== null && $this->getAction() !== $redirectTarget) {
		$this->redirect($redirectTarget);
	}
}


/**
 * Handles the 'summary' action.
 * Redirects based on the completeness of the order:
 * If carrier is missing but cart has items, redirects to 'products'.
 * If the carrier is missing and the cart is empty, redirects to 'default'.
 * If customer data is missing but carrier is selected, redirects to 'customer'.
 */
public function actionSummary(): void
{
	$draft = $this->getOrderDraft();
	$customer = $draft->customer;
	$carrier = $draft->carrier;
	$hasItems = $this->hasItems();

	$redirectTarget = match (true) {
		$carrier === null && $hasItems => self::PageDelivery,
		$carrier === null && !$hasItems => self::PageProducts,
		$customer === null && $carrier !== null => self::PageCustomer,
		default => null,
	};

	if ($redirectTarget !== null && $this->getAction() !== $redirectTarget) {
		$this->redirect($redirectTarget);
	}
}
```
