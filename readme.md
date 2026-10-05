# Drago Commerce (development version)

Storefront and checkout components for Nette applications.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://github.com/drago-ex/commerce/blob/main/license)
[![PHP version](https://badge.fury.io/ph/drago-ex%2Fcommerce.svg)](https://badge.fury.io/ph/drago-ex%2Fcommerce)
[![Tests](https://github.com/drago-ex/commerce/actions/workflows/tests.yml/badge.svg)](https://github.com/drago-ex/commerce/actions/workflows/tests.yml)
[![Coding Style](https://github.com/drago-ex/commerce/actions/workflows/coding-style.yml/badge.svg)](https://github.com/drago-ex/commerce/actions/workflows/coding-style.yml)

The package provides a product catalog and detail view, a session-based shopping cart, and a guided checkout. Customers can select product variants, use discount codes, choose delivery and payment options, enter their details, and submit an order. Commerce stores the order and updates stock in a database transaction.

Included features:

- Product variants with their own stock, optional price overrides, and additional product images.
- Product percentage discounts and discount codes with percentage or fixed values, validity dates, usage limits, and optional minimum order amounts.
- A mini cart, quantity changes, item removal, and a checkout summary with carrier and payment prices.
- Order and cart events for custom behavior in the host application.

Commerce records the selected payment method but does not process payments through a gateway, arrange shipping, send order emails, or include a shop administration interface. The host application provides product, carrier, payment, and discount-code records, and can add those integrations using Commerce events.

## Requirements

- PHP >= 8.3
- Nette Framework
- Composer
- A configured Dibi database connection
- Node.js and Vite for the optional frontend assets

## Installation

Install the package with Composer:

```bash
composer require drago-ex/commerce
```

## Database Setup

The `migrations/` directory contains one migration per table, followed by a single example-data migration. The table definitions use the same format as the other Drago packages. With [drago-ex/migration](https://github.com/drago-ex/migration), run the directory so the files are applied in filename order:

```bash
php vendor/bin/migration db:migrate vendor/drago-ex/commerce/migrations
```

Files `001`–`013` create carriers, customers, payment methods, product categories, products, discount codes, product attributes and values, variants and their values, orders and order lines, and product images. `014_commerce_seed.sql` imports the example carriers, payments, customers, products, orders, discount codes, variants, and images. It is intended for development or demo databases; omit it if you do not want example records.

This migration sequence replaces the earlier development files. Reset a development database that already used the old sequence before running these migrations. Once the package is used in a shared or production database, keep applied migration files unchanged and add a new file for each future schema change; the migration tool records filenames and checksums.

## Configuration

Register the phone input bridge and Commerce extension in `config.neon`:

```neon
extensions:
	- Nepada\Bridges\PhoneNumberInputDI\PhoneNumberInputExtension
	commerce: Drago\Commerce\DI\CommerceExtension
```

Configure currency, formatting, and phone-number defaults:

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

`geoLite2Path` is optional. It enables automatic phone region detection when the host application provides a MaxMind GeoLite2 City database.

Register the checkout services so Nette DI can create the checkout flow:

```neon
services:
	- Drago\Commerce\Domain\Checkout\CheckoutProcess
	- Drago\Commerce\Domain\Checkout\CheckoutSteps
```

The default `CheckoutSteps` defines the actions used by the checkout: `shoppingCart`, `delivery`, `customer`, `summary`, and `done`.

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

Install dependencies and import Commerce in the Vite entry point:

```bash
npm install
```

```js
import naja from 'naja';
import Commerce from 'drago-commerce';
import 'drago-commerce/styles';

naja.initialize();
new Commerce().initialize(naja);
```

This integration submits cart quantity changes through Naja and displays a loading spinner during AJAX requests.

## Presenter Setup

### 1. Add the Commerce trait

Use `CommerceControl` in the presenter that renders the shop. The trait injects these controls and configures checkout-step navigation automatically:

- `miniCartControl` — cart summary for the page layout.
- `productControl` — product listing.
- `productDetailControl` — selected product detail and variant selection.
- `shoppingCartControl` — cart contents, quantity updates, and discount code form.
- `deliveryControl` — carrier and payment selection.
- `customerControl` — contact and billing details.
- `summaryOrderControl` — final order review and submission.

```php
use Drago\Commerce\UI\CommerceControl;

final class HomePresenter extends BasePresenter
{
	use CommerceControl;
}
```

The trait's Nette injection method receives the controls and `CheckoutProcess`; they do not need to be added to the presenter's constructor.

### 2. Add the checkout guard

Inject `CheckoutProcess` into the presenter only if it should prevent visitors from opening checkout steps before meeting their prerequisites. Call the resolver from `startup()` so it checks every action:

```php
use Drago\Commerce\Domain\Checkout\CheckoutProcess;

public function __construct(
	protected CheckoutProcess $checkoutProcess,
) {
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

The guard redirects delivery, customer, or summary actions to the first missing prerequisite. For example, a customer with an empty cart cannot jump directly to the customer form.

### 3. Create the component factories you use

Nette creates a component when its `{control ...}` is rendered. Each factory returns the corresponding control injected by `CommerceControl`. Set its translator if the application uses `drago-ex/translator` or another compatible translator; otherwise the bundled templates use their default English labels.

| Factory method | Control | What it renders |
| --- | --- | --- |
| `createComponentMiniCart()` | `MiniCartControl` | Cart link and item count, usually in the layout. |
| `createComponentProduct()` | `ProductControl` | Active product catalog. |
| `createComponentProductDetail()` | `ProductDetailControl` | Selected product, variants, and add-to-cart form. Set the product ID first. |
| `createComponentShoppingCart()` | `SummaryCartControl` | Cart contents, quantity changes, and discount code form. |
| `createComponentDelivery()` | `DeliveryControl` | Carrier and payment selection. |
| `createComponentCustomer()` | `CustomerControl` | Contact, billing, and optional order note. |
| `createComponentSummaryOrder()` | `SummaryOrderControl` | Order review and order submission. |

For example, a regular component factory can set the translator before returning the injected control:

```php
protected function createComponentDelivery(): DeliveryControl
{
	$control = $this->deliveryControl;
	$control->translator = $this->getTranslator();
	return $control;
}
```

For a product detail component, pass the selected product ID before rendering it. Declare an `id` presenter parameter for the product identifier:

```php
use Drago\Commerce\UI\Product\ProductDetailControl;
use Nette\Application\Attributes\Persistent;

#[Persistent]
public int $id;

protected function createComponentProductDetail(): ProductDetailControl
{
	$control = $this->productDetailControl;
	$control->setProductId($this->id);
	$control->translator = $this->getTranslator();
	return $control;
}
```

`ProductRepository` is only needed in the presenter when the presenter itself queries products; the bundled product controls already use the repository internally.

## Latte Templates

Render the mini cart in the site layout so it is available throughout the shop:

```latte
{snippet cart}
	{control miniCart}
{/snippet}
```

Add the relevant control to each page template:

| Page | Latte |
| --- | --- |
| Product listing | `{control product}` |
| Product detail | `{control productDetail}` |
| Shopping cart | `{snippet shoppingCart}{control shoppingCart}{/snippet}` |
| Delivery and payment | `{snippet delivery}{control delivery}{/snippet}` |
| Customer details | `{snippet customer}{control customer}{/snippet}` |
| Order review | `{snippet summaryOrder}{control summaryOrder}{/snippet}` |

The completion page is part of the host application's presenter. For example:

```latte
{block content}
	<h1>{_'Order completed'}</h1>
	<p class="alert alert-success">{_'Thank you, your order has been successfully submitted.'}</p>
{/block}
```

## Optional Customization

### Custom templates

Every UI control has a `templateControl` property. Set it in the component factory to render your own Latte file instead of the bundled template:

```php
$control = $this->deliveryControl;
$control->templateControl = __DIR__ . '/templates/Delivery/customTemplate.latte';
return $control;
```

### Checkout step names

To change default checkout action names, provide a custom `CheckoutSteps` service and inject it into `CheckoutProcess`. The names must match the presenter actions and the corresponding page templates. For example:

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

The constructor keys can override `products`, `delivery`, `customer`, `summary`, `shoppingCart`, and `orderDone`. Checkout controls are configured by the trait using these step names.
