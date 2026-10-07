# Drago Commerce

Reusable storefront and checkout components for Nette applications.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://github.com/drago-ex/commerce/blob/main/license)
[![PHP version](https://badge.fury.io/ph/drago-ex%2Fcommerce.svg)](https://badge.fury.io/ph/drago-ex%2Fcommerce)
[![Tests](https://github.com/drago-ex/commerce/actions/workflows/tests.yml/badge.svg)](https://github.com/drago-ex/commerce/actions/workflows/tests.yml)
[![Coding Style](https://github.com/drago-ex/commerce/actions/workflows/coding-style.yml/badge.svg)](https://github.com/drago-ex/commerce/actions/workflows/coding-style.yml)

## What it does

- Product listing and detail pages, including product variants and stock.
- Session shopping cart with quantity updates and discount codes.
- Checkout for delivery, payment, customer details, and order confirmation.
- Transactional order creation with atomic stock reservation and order-line snapshots.
- Events for customizing product pricing and reacting to checkout changes.

## Requirements

- PHP >= 8.3
- Nette Framework and Composer
- A Dibi database connection
- Node.js and Vite only if you use the optional frontend assets

## Installation

```bash
composer require drago-ex/commerce
```

Run the package migrations with [drago-ex/migration](https://github.com/drago-ex/migration):

```bash
php vendor/bin/migration db:migrate vendor/drago-ex/commerce/migrations
```

Optional demo data for development:

```bash
php vendor/bin/migration db:migrate vendor/drago-ex/commerce/migrations-demo
```

Never edit migrations that have already run on a shared or production database; add a new migration instead.

## Configuration

Register the extension and the phone-number input bridge in your Nette configuration:

```neon
extensions:
	phoneNumberInput: Nepada\Bridges\PhoneNumberInputDI\PhoneNumberInputExtension
	commerce: Drago\Commerce\DI\CommerceExtension

commerce:
	currency: CZK
	moneyFormat: cs_CZ
	moneyFractionDigits: 0

services:
	- Drago\Commerce\Domain\Checkout\CheckoutProcess
	- Drago\Commerce\Domain\Checkout\CheckoutSteps
```

Set the Nette session expiration to at least one day. Cart, checkout, and discount-code data expire after one day:

```neon
session:
	expiration: 14 days
```

Available `commerce` options:

| Option | Default | Description |
| --- | --- | --- |
| `currency` | `EUR` | Currency used for prices |
| `moneyFormat` | `de_DE` | Locale used to format prices |
| `moneySymbol` | `''` | Optional currency-symbol override |
| `moneyFractionDigits` | `2` | Number of displayed decimal places |
| `itemsPerPage` | `12` | Products per page; `0` disables pagination |
| `defaultRegionCode` | none | Default phone region, e.g. `CZ`, or `['autoDetect', 'CZ']` to detect and fall back |
| `allowedRegionPhoneNumber` | any | One or more allowed phone-region codes |
| `postCodeOnRegionPhone` | `false` | Validate the postal code against the phone region |
| `geoLite2Path` | none | Path to a GeoLite2 City database for phone-region detection |

## Use in a presenter

Add the `CommerceControl` trait and create factories for the controls you use. The trait injects the controls and configures checkout navigation.

```php
use Drago\Commerce\UI\CommerceControl;
use Drago\Commerce\UI\Product\ProductDetailControl;
use Drago\Commerce\UI\ShoppingCart\MiniCartControl;
use Drago\Commerce\Domain\Checkout\CheckoutProcess;

final class ShopPresenter extends BasePresenter
{
	use CommerceControl;

	public function __construct(
		private CheckoutProcess $checkoutProcess,
	) {
		parent::__construct();
	}

	protected function createComponentMiniCart(): MiniCartControl
	{
		$control = $this->miniCartControl;
		$control->translator = $this->getTranslator();
		return $control;
	}
}
```

Create a component factory only for controls you want to display. Each factory returns the matching property injected by `CommerceControl`:

- **Storefront:** `createComponentProduct()` for the product listing and `createComponentProductDetail()` for a product detail page. Set the product ID on the detail control before returning it.
- **Cart:** `createComponentMiniCart()` for the cart link and `createComponentShoppingCart()` for the cart page.
- **Checkout:** `createComponentDelivery()`, `createComponentCustomer()`, and `createComponentSummaryOrder()` for delivery and payment, customer details, and final order review.

When you use translations, set the control's `translator` in its factory. A product detail factory can also select the product from a presenter parameter:

```php
protected function createComponentProductDetail(): ProductDetailControl
{
	$control = $this->productDetailControl;
	$control->setProductId((int) $this->getParameter('id'));
	$control->translator = $this->getTranslator();
	return $control;
}
```

## Templates

Render the controls in your Latte templates. Put the mini cart in the layout and wrap controls in snippets when they should update with Naja:

```latte
{snippet cart}
	{control miniCart}
{/snippet}

{control product}
{control productDetail}

{snippet shoppingCart}
	{control shoppingCart}
{/snippet}

{snippet delivery}
	{control delivery}
{/snippet}

{snippet customer}
	{control customer}
{/snippet}

{snippet summaryOrder}
	{control summaryOrder}
{/snippet}
```

The order-completion page is provided by the application as the `done` action. Each control supports a custom template file through `templateControl`:

```php
$control->templateControl = __DIR__ . '/templates/Delivery/custom.latte';
```

A custom order-summary template must include the hidden `orderToken` field in the `sendOrder` form so checkout can verify that the customer is confirming the displayed order:

```latte
<form n:name="sendOrder">
	<input n:name="orderToken">
	<input n:name="send">
</form>
```

## Checkout and prices

The default checkout actions are `default`, `shoppingCart`, `delivery`, `customer`, `summary`, and `done`. Register `CheckoutProcess` and use it in `startup()` to redirect customers to the first checkout step whose requirements are not met:

```php
public function startup(): void
{
	parent::startup();

	$target = $this->checkoutProcess->getRedirectTargetForAction($this->getAction());
	if ($target !== null && $target !== $this->getAction()) {
		$this->redirect($target);
	}
}
```

To use different presenter action names, replace the default `CheckoutSteps` and `CheckoutProcess` service entries with named definitions. The action names must match the actions in your presenter:

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

Products with active variants must be purchased through a variant. A variant can use the product price and discount, or have its own price; variant-specific prices do not receive the product discount. Checkout refreshes catalog and delivery prices before order placement and asks the customer to review changes.

## Events

Register callable listeners on the `EventDispatcher` service to customize behavior:

- `ProductAddedToCart` — adjust a product's price before it is added or repriced.
- `CartItemChanged` and `CartItemRemoved` — react to cart updates.
- `CustomerUpdated` and `DeliveryOptionsChanged` — react to checkout changes.
- `OrderPlaced` — react after the order has been saved.

Listeners run synchronously. An `OrderPlaced` listener failure is logged and does not undo the saved order.

### Order confirmation email

Order confirmation emails are optional. Install `nette/mail`, configure its SMTP transport, then set `commerce.orderEmail.from` to enable the built-in listener:

```bash
composer require nette/mail
```

```neon
mail:
	smtp: true
	host: localhost
	port: 25
	username: ''
	password: ''
	encryption: null

commerce:
	orderEmail:
		from: 'Shop <shop@example.com>'
		mailer: mail.mailer
		storeName: 'My Shop' # displayed prominently in the email header
		storeEmail: support@example.com
		# templateFile: %appDir%/Mail/order-confirmation.latte
```

The optional `mailer` setting selects the service implementing `Nette\Mail\Mailer`. `templateFile` can point to a custom Latte template; its type is `Drago\Commerce\Mail\OrderConfirmationTemplate`. If using `drago-ex/translator`, add the package translation directory to `translateDirs` so Czech emails use the included translations:

```neon
translator:
	autoFinder: false
	translateDirs:
		- %vendorDir%/drago-ex/commerce/src/Drago/Commerce/Translate
		- %appDir%/Translate
```

The current request's translator determines the email language. Emails are sent synchronously during checkout, so SMTP latency delays the response; a queue can be added by listening to `OrderPlaced` and storing its item snapshot for later processing.

Migration `016_order_delivery_snapshots.sql` stores the carrier and payment names with each order, so confirmation data does not change when those options are renamed.

## Frontend assets (optional)

Add the package to your `package.json`, then import its assets and initialize the Naja integration:

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
