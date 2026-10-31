# Drago Commerce

Znovupoužitelné komponenty obchodu a objednávky pro aplikace v Nette.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://github.com/drago-ex/commerce/blob/main/license)
[![PHP version](https://badge.fury.io/ph/drago-ex%2Fcommerce.svg)](https://badge.fury.io/ph/drago-ex%2Fcommerce)
[![Tests](https://github.com/drago-ex/commerce/actions/workflows/tests.yml/badge.svg)](https://github.com/drago-ex/commerce/actions/workflows/tests.yml)
[![Coding Style](https://github.com/drago-ex/commerce/actions/workflows/coding-style.yml/badge.svg)](https://github.com/drago-ex/commerce/actions/workflows/coding-style.yml)

## Požadavky

- PHP >= 8.3, < 9
- Nette Framework a Composer
- Připojení k databázi přes Dibi
- Node.js a Vite jen pro volitelné assety frontendu

## Instalace

```bash
composer require drago-ex/commerce
```

Spusťte migrace balíčku přes [drago-ex/migration](https://github.com/drago-ex/migration):

```bash
php vendor/bin/migration db:migrate vendor/drago-ex/commerce/migrations
```

Volitelná ukázková data pro vývoj:

```bash
php vendor/bin/migration db:migrate vendor/drago-ex/commerce/migrations-demo
```

Migrace, které už proběhly na sdílené nebo produkční databázi, nikdy neupravujte; přidejte místo toho novou migraci.

## Co balíček dělá

- Výpis a detail produktů včetně variant a skladu.
- Košík v session se změnou množství a slevovými kódy.
- Objednávka s dopravou, platbou, údaji zákazníka a potvrzením.
- Vytvoření objednávky v transakci s atomickou rezervací skladu a uloženými kopiemi položek.
- Události pro úpravu cen produktů a reakce na změny v objednávce.

## Konfigurace

V konfiguraci Nette zaregistrujte rozšíření a most pro pole telefonního čísla:

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

Nastavte platnost session v Nette aspoň na jeden den. Data košíku, objednávky a slevových kódů vyprší po jednom dni:

```neon
session:
	expiration: 14 days
```

Dostupné volby `commerce`:

| Volba | Výchozí | Popis |
| --- | --- | --- |
| `currency` | `EUR` | Měna cen |
| `moneyFormat` | `de_DE` | Locale pro formátování cen |
| `moneySymbol` | `''` | Volitelné přepsání symbolu měny |
| `moneyFractionDigits` | `2` | Počet zobrazených desetinných míst |
| `itemsPerPage` | `12` | Produktů na stránku; `0` stránkování vypne |
| `defaultRegionCode` | žádná | Výchozí oblast telefonu, např. `CZ`, nebo `['autoDetect', 'CZ']` pro zjištění se záložní hodnotou |
| `allowedRegionPhoneNumber` | libovolná | Jeden nebo více povolených kódů oblasti telefonu |
| `countries` | `[CZ, SK]` | Země doručení nabízené ve formuláři zákazníka, kódy ISO 3166-1 alpha-2; předvybraná je první, pokud GeoLite2 nenajde jinou z nich |
| `validatePostCode` | `true` | Ověřit a naformátovat PSČ podle vybrané země (země, které brick/postcode nezná, nechají PSČ, jak bylo zadané) |
| `geoLite2Path` | žádná | Cesta k databázi GeoLite2 City pro zjištění oblasti telefonu a země |

## Použití v presenteru

Přidejte trait `CommerceControl` a vytvořte továrny pro komponenty, které používáte. Trait komponenty předá a nastaví navigaci objednávky.

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

Továrnu komponenty vytvořte jen pro komponenty, které chcete zobrazit. Každá továrna vrací odpovídající vlastnost předanou traitem `CommerceControl`:

- **Obchod:** `createComponentProduct()` pro výpis produktů a `createComponentProductDetail()` pro detail produktu. Před vrácením nastavte komponentě detailu ID produktu.
- **Košík:** `createComponentMiniCart()` pro odkaz na košík a `createComponentShoppingCart()` pro stránku košíku.
- **Objednávka:** `createComponentDelivery()`, `createComponentCustomer()` a `createComponentSummaryOrder()` pro dopravu a platbu, údaje zákazníka a závěrečnou kontrolu objednávky.

Když používáte překlady, nastavte v továrně `translator` komponenty. Továrna detailu produktu může produkt vybrat i podle parametru presenteru:

```php
protected function createComponentProductDetail(): ProductDetailControl
{
	$control = $this->productDetailControl;
	$control->setProductId((int) $this->getParameter('id'));
	$control->translator = $this->getTranslator();
	return $control;
}
```

## Šablony

Komponenty vykreslete v šablonách Latte. Mini košík dejte do layoutu a komponenty, které se mají aktualizovat přes Naja, obalte snippety:

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

Stránku dokončení objednávky poskytuje aplikace jako akci `done`. Každá komponenta podporuje vlastní soubor šablony přes `templateControl`:

```php
$control->templateControl = __DIR__ . '/templates/Delivery/custom.latte';
```

Vlastní šablona souhrnu objednávky musí ve formuláři `sendOrder` obsahovat skryté pole `orderToken`, aby objednávka mohla ověřit, že zákazník potvrzuje zobrazenou objednávku:

```latte
<form n:name="sendOrder">
	<input n:name="orderToken">
	<input n:name="send">
</form>
```

## Objednávka a ceny

Výchozí akce objednávky jsou `default`, `shoppingCart`, `delivery`, `customer`, `summary` a `done`. Zaregistrujte `CheckoutProcess` a v `startup()` s ním přesměrujte zákazníka na první krok objednávky, jehož podmínky nejsou splněné:

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

Pro jiné názvy akcí presenteru nahraďte výchozí služby `CheckoutSteps` a `CheckoutProcess` pojmenovanými definicemi. Názvy akcí musí odpovídat akcím vašeho presenteru:

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

Produkty s aktivními variantami se kupují přes variantu. Varianta může používat cenu a slevu produktu, nebo mít vlastní cenu; na vlastní ceny variant se sleva produktu neuplatní. Objednávka před odesláním obnoví ceny katalogu a dopravy a požádá zákazníka o kontrolu změn.

## Stavy objednávky

Vytvořená objednávka je ve stavu `new`. `OrderStatusService` mění její stav podle `OrderStatus` a zaplacení zvlášť:

| Stav | Může přejít na |
| --- | --- |
| `new` | `processing`, `cancelled` |
| `processing` | `shipped`, `cancelled` |
| `shipped` | `completed` |
| `completed`, `cancelled` | — |

```php
$orderStatusService->change($orderId, OrderStatus::Shipped);
$orderStatusService->markPaid($orderId); // paid_at, false when already paid
```

Nepovolená změna vyhodí `OrderStatusException`. Zrušení vrátí objednané zboží na sklad a použití slevového kódu. Zaplacení se eviduje odděleně od stavu (`paid_at`), protože převodem se platí před odesláním a dobírkou až po něm.

## Události

Pro úpravu chování zaregistrujte posluchače u služby `EventDispatcher`:

- `ProductAddedToCart` — úprava ceny produktu před přidáním nebo přepočtem.
- `CartItemChanged` a `CartItemRemoved` — reakce na změny košíku.
- `CustomerUpdated` a `DeliveryOptionsChanged` — reakce na změny v objednávce.
- `OrderPlaced` — reakce po uložení objednávky.
- `OrderStatusChanged` — reakce po změně stavu objednávky (např. e-mail o odeslání).

Posluchači běží synchronně. Chyba posluchače `OrderPlaced` se zaloguje a uloženou objednávku nevrátí.

### E-mail s potvrzením objednávky

Volitelné. Pro zapnutí nainstalujte `nette/mail`, nastavte jeho mailer a nastavte `commerce.orderEmail.from`:

```bash
composer require nette/mail
```

```neon
commerce:
	orderEmail:
		from: 'Shop <shop@example.com>'
		mailer: mail.mailer # service implementing Nette\Mail\Mailer
		storeName: 'My Shop'
		storeEmail: support@example.com
		# templateFile: %appDir%/Mail/order-email.latte
```

`templateFile` ukazuje na vlastní šablonu Latte typu `Drago\Commerce\Mail\OrderEmailTemplate`. Texty jdou přes nastavený překladač; `drago-ex/translator` dodané překlady najde sám, jiné překladače mohou načíst `src/Commerce`. Jazyk se bere z parametru `lang` presenteru a předá se metodě překladače `setTranslate()`, pokud ji má.

E-mail se odesílá synchronně; chyba se zaloguje a objednávku neovlivní. Každá objednávka si ukládá názvy dopravce a platby tak, jak byly při jejím vytvoření.

---

## Volitelné doplňky

Balíček funguje i bez nich. Pokud je projekt potřebuje, nastavte je ručně podle popisu níže.

### Assety frontendu

Přidejte balíček do `package.json`, potom importujte jeho assety a inicializujte zapojení do Naja:

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
