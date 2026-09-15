# Drago Commerce

A simple shopping cart and multi-step checkout component for Nette Framework.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://github.com/drago-ex/commerce/blob/main/license)

## Requirements
- PHP >= 8.3
- Nette Framework 3.1+
- Composer

## Installation

```bash
composer require drago-ex/commerce
```

### Database Setup
Run the SQL migrations in your database:
- `migrations/001_commerce.sql` – creates tables (`products`, `carrier`, `payment`, `customers`, `orders`, `orders_products`, `discount_codes`).
- `migrations/002.commerce_seed.sql` *(optional)* – sample carriers, payment methods, and products.

---

## Configuration (`app.neon` / `config.neon`)

Register the extensions and configure settings:

```neon
extensions:
    - Nepada\Bridges\PhoneNumberInputDI\PhoneNumberInputExtension
    commerce: Drago\Commerce\DI\CommerceExtension

commerce:
    currency: CZK
    moneyFormat: cs_CZ
    moneySymbol: ''
    moneyFractionDigits: 0
    defaultRegionCode: ['autoDetect', 'CZ']
    allowedRegionPhoneNumber: CZ
    postCodeOnRegionPhone: true

services:
    - Drago\Commerce\Domain\Checkout\CheckoutProcess
    - Drago\Commerce\Domain\Checkout\CheckoutSteps
```

---

## Frontend Assets (Vite + Naja)

Add the package to `package.json`:

```json
{
  "dependencies": {
    "drago-commerce": "file:vendor/drago-ex/commerce"
  }
}
```

Install and initialize in your JavaScript entry point:

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

---

## Presenter Setup

Use the `CommerceControl` trait and inject `CheckoutProcess` into your presenter:

```php
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

final class HomePresenter extends BasePresenter
{
    use CommerceControl;

    public function __construct(
        protected CheckoutProcess $checkoutProcess,
    ) {
        parent::__construct();
    }

    // Step guards: redirect back if previous checkout steps are missing
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

    // Components factory methods
    protected function createComponentMiniCart(): MiniCartControl
    {
        $control = $this->miniCartControl;
        $control->setLinkRedirectTarget($this->checkoutProcess->steps()->shoppingCart);
        return $control;
    }

    protected function createComponentProduct(): ProductControl
    {
        return $this->productControl;
    }

    protected function createComponentShoppingCart(): SummaryCartControl
    {
        $control = $this->shoppingCartControl;
        $control->setSteps($this->checkoutProcess->getSteps());
        $control->setCompletedSteps($this->checkoutProcess->getCompletedSteps());
        $control->setCurrentStep($this->checkoutProcess->steps()->shoppingCart);
        $control->setLinkRedirectTarget($this->checkoutProcess->steps()->delivery);
        return $control;
    }

    protected function createComponentDelivery(): DeliveryControl
    {
        $control = $this->deliveryControl;
        $control->setSteps($this->checkoutProcess->getSteps());
        $control->setCompletedSteps($this->checkoutProcess->getCompletedSteps());
        $control->setCurrentStep($this->checkoutProcess->steps()->delivery);
        $control->setLinkRedirectTarget($this->checkoutProcess->steps()->customer);
        return $control;
    }

    protected function createComponentCustomer(): CustomerControl
    {
        $control = $this->customerControl;
        $control->setSteps($this->checkoutProcess->getSteps());
        $control->setCompletedSteps($this->checkoutProcess->getCompletedSteps());
        $control->setCurrentStep($this->checkoutProcess->steps()->customer);
        $control->setLinkRedirectTarget($this->checkoutProcess->steps()->summary);
        return $control;
    }

    protected function createComponentSummaryOrder(): SummaryOrderControl
    {
        $control = $this->summaryOrderControl;
        $control->setSteps($this->checkoutProcess->getSteps());
        $control->setCompletedSteps($this->checkoutProcess->getCompletedSteps());
        $control->setCurrentStep($this->checkoutProcess->steps()->summary);
        $control->setLinkRedirectTarget($this->checkoutProcess->steps()->orderDone);
        return $control;
    }
}
```

---

## Latte Templates

### 1. Layout (`@layout.latte`)
Include the mini cart widget in your navbar / header:

```latte
<header class="container mb-3">
    <div class="d-flex justify-content-between align-items-center py-2">
        <div class="fw-bold fs-4">Shop</div>
        {snippet cart}
            {control miniCart}
        {/snippet}
    </div>
</header>

<main class="container">
    {snippet message}
        <div n:foreach="$flashes as $flash" n:class="flash, $flash->type">
            {$flash->message}
        </div>
    {/snippet}

    {include content}
</main>
```

### 2. Product Catalog (`default.latte`)
```latte
{block content}
    <h1 n:block="title">Produkty</h1>
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
    <h1 n:block="title">Objednávka dokončena</h1>
    <p class="alert alert-success">Děkujeme, vaše objednávka byla úspěšně odeslána.</p>
    <a n:href="default" class="btn btn-primary">Zpět na nabídku</a>
{/block}
```

---

## Customization

### Custom Template Files
Every control supports overriding the default template via the `templateControl` property:

```php
protected function createComponentDelivery(): DeliveryControl
{
    $control = $this->deliveryControl;
    $control->templateControl = __DIR__ . '/templates/customDelivery.latte';
    // ...
    return $control;
}
```

### Custom Checkout Steps
To rename or customize step names (e.g. for routing or localization), configure `CheckoutSteps` in NEON:

```neon
services:
    checkoutSteps:
        factory: Drago\Commerce\Domain\Checkout\CheckoutSteps
        arguments:
            -
                products: 'default'
                delivery: 'doprava'
                customer: 'udaje'
                summary: 'rekapitulace'
                shoppingCart: 'kosik'
                orderDone: 'hotovo'

    checkoutProcess:
        factory: Drago\Commerce\Domain\Checkout\CheckoutProcess
        arguments:
            - @Drago\Commerce\Service\ShoppingCartSession
            - @Drago\Commerce\Service\OrderSession
            - @checkoutSteps
```
