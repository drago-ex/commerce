# Drago Commerce (development version)

Storefront and checkout components for Nette applications.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://github.com/drago-ex/commerce/blob/main/license)
[![PHP version](https://badge.fury.io/ph/drago-ex%2Fcommerce.svg)](https://badge.fury.io/ph/drago-ex%2Fcommerce)
[![Tests](https://github.com/drago-ex/commerce/actions/workflows/tests.yml/badge.svg)](https://github.com/drago-ex/commerce/actions/workflows/tests.yml)
[![Coding Style](https://github.com/drago-ex/commerce/actions/workflows/coding-style.yml/badge.svg)](https://github.com/drago-ex/commerce/actions/workflows/coding-style.yml)

## What it does

- Paged product listing and detail with variants (own stock, optional own price, extra images).
- Percentage discounts per product and discount codes (percent or fixed amount, validity dates, usage limit, minimum order amount).
- Session cart with mini cart, quantity changes and item removal.
- Checkout: cart, delivery and payment, customer details, summary.
- The order is saved in one transaction with an atomic stock update. Prices are re-checked right before saving: if a price changed since the item was added, or the total differs from the one shown on the summary (for example an expired discount code), the customer sees the new price and confirms again.
- Order lines keep the product name and variant label as they were at purchase, and every order stores its own copy of the customer details.
- Events for custom behavior in your application.

Not included: payment gateway, shipping integration, order e-mails, shop administration. Your application provides the product, carrier, payment and discount-code records.

## Requirements

PHP >= 8.3, Nette Framework, a Dibi connection, Composer. Node.js and Vite for the optional frontend assets.

## Installation

```bash
composer require drago-ex/commerce
```

### 1. Database

Run the migrations with [drago-ex/migration](https://github.com/drago-ex/migration):

```bash
php vendor/bin/migration db:migrate vendor/drago-ex/commerce/migrations
```

Optional example data (carriers, payments, products, variants, discount codes, example orders). Development only:

```bash
php vendor/bin/migration db:migrate vendor/drago-ex/commerce/migrations-demo
```

Once the package is used in a shared or production database, never edit applied migration files; add a new one for each schema change.

### 2. Configuration

```neon
extensions:
	- Nepada\Bridges\PhoneNumberInputDI\PhoneNumberInputExtension
	commerce: Drago\Commerce\DI\CommerceExtension

commerce:
	currency: CZK
	moneyFormat: cs_CZ
	moneyFractionDigits: 0

services:
	- Drago\Commerce\Domain\Checkout\CheckoutProcess
	- Drago\Commerce\Domain\Checkout\CheckoutSteps
```

Set the Nette session expiration to at least 1 day (`session: expiration: 14 days`); the cart, discount code and order data expire after 1 day, and Nette warns when the session expires sooner.

### 3. Presenter

Use the trait. It injects all controls and sets up checkout navigation:

```php
use Drago\Commerce\UI\CommerceControl;

final class HomePresenter extends BasePresenter
{
	use CommerceControl;
}
```

Create a component factory for each control you render. Set the translator if you use one:

| Factory | Control | Renders |
| --- | --- | --- |
| `createComponentMiniCart()` | `miniCartControl` | Cart link and item count |
| `createComponentProduct()` | `productControl` | Product listing |
| `createComponentProductDetail()` | `productDetailControl` | Product detail, variants, add to cart (call `setProductId()` first) |
| `createComponentShoppingCart()` | `shoppingCartControl` | Cart, quantities, discount code |
| `createComponentDelivery()` | `deliveryControl` | Carrier and payment |
| `createComponentCustomer()` | `customerControl` | Customer details |
| `createComponentSummaryOrder()` | `summaryOrderControl` | Review and submit |

```php
protected function createComponentDelivery(): DeliveryControl
{
	$control = $this->deliveryControl;
	$control->translator = $this->getTranslator();
	return $control;
}
```

Optional checkout guard: it redirects a step to the first missing prerequisite (for example, the customer form with an empty cart).

```php
public function __construct(protected CheckoutProcess $checkoutProcess)
{
	parent::__construct();
}

public function startup(): void
{
	parent::startup();
	$target = $this->checkoutProcess->getRedirectTargetForAction($this->getAction());
	if ($target !== null && $target !== $this->getAction()) {
		$this->redirect($target);
	}
}
```

### 4. Templates

```latte
{snippet cart}{control miniCart}{/snippet}          {* layout *}

{control product}                                   {* listing *}
{control productDetail}                             {* detail *}
{snippet shoppingCart}{control shoppingCart}{/snippet}
{snippet delivery}{control delivery}{/snippet}
{snippet customer}{control customer}{/snippet}
{snippet summaryOrder}{control summaryOrder}{/snippet}
```

The completion page (action `done`) is your own template.

### 5. Frontend (optional)

AJAX quantity changes and a loading spinner. In `package.json`:

```json
{
	"type": "module",
	"dependencies": {
		"drago-commerce": "file:vendor/drago-ex/commerce"
	}
}
```

```js
import naja from 'naja';
import Commerce from 'drago-commerce';
import 'drago-commerce/styles';

naja.initialize();
new Commerce().initialize(naja);
```

## Configuration options

| Option | Default | Meaning |
| --- | --- | --- |
| `currency` | `EUR` | Currency code |
| `moneyFormat` | `de_DE` | Locale used to format prices |
| `moneySymbol` | `''` | Overrides the currency symbol |
| `moneyFractionDigits` | `2` | Decimal places shown |
| `defaultRegionCode` | none | Default phone region: a code (`CZ`), or `['autoDetect', 'CZ']` to detect it and fall back to `CZ` |
| `allowedRegionPhoneNumber` | any | Allowed phone region code or list of codes |
| `postCodeOnRegionPhone` | `false` | Validate the postal code against the phone region |
| `itemsPerPage` | `12` | Products per page in the listing; `0` shows all |
| `geoLite2Path` | none | MaxMind GeoLite2 City database for phone region detection |

### Templates

Every control has a `templateControl` property for your own Latte file:

```php
$control->templateControl = __DIR__ . '/templates/Delivery/custom.latte';
```

### Checkout step names

Defaults: `products` (`default`), `shoppingCart`, `delivery`, `customer`, `summary`, `orderDone` (`done`). Override them with a custom `CheckoutSteps`; the names must match your presenter actions:

```neon
services:
	checkoutSteps:
		factory: Drago\Commerce\Domain\Checkout\CheckoutSteps
		arguments:
			- {delivery: shipping, customer: billing}
	checkoutProcess:
		factory: Drago\Commerce\Domain\Checkout\CheckoutProcess
		arguments:
			- @Drago\Commerce\Service\ShoppingCartSession
			- @Drago\Commerce\Service\OrderSession
			- @checkoutSteps
```

### Events

Register listeners in `services.neon` with `addListener(EventClass, @listener)`. A listener must be callable. Listeners run synchronously; an exception reaches the dispatching code, except for `OrderPlaced`, where the order is already saved and the failure is only logged.

| Event | Fired when |
| --- | --- |
| `ProductAddedToCart` | An item is added, before it is stored. `setPrice()` changes the price. Dispatched again when checkout reprices a line. |
| `CartItemChanged` | The quantity of a cart line changes. |
| `CartItemRemoved` | A cart line is removed. |
| `CustomerUpdated` | The customer step is completed. |
| `DeliveryOptionsChanged` | Carrier and payment are chosen. |
| `OrderPlaced` | The order is saved. Read `items`, because the cart is emptied right after the listeners run. |

`ProductAddedToCart::$product->price` is the price before the product discount: the variant's own price, otherwise the product price. The discount is applied afterwards, only if no listener changed the price and the variant has no own price. `OrderLoggerListener` writes placed orders to the Tracy log `order`: order ID, customer ID, items, prices and delivery. Customer contact details are not logged.

### Prices and variants

- A product with an active variant is bought only through a variant. The listing shows the summed variant stock and the lowest price.
- A variant without a price inherits the product price and discount. A variant with its own price is sold at that price without the product discount.
- The schema does not prevent duplicate attribute combinations on a product; keep them consistent in whatever manages the data.
