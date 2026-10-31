# Agent Guide

Use this file as package-specific guidance when working in this repository. Keep changes aligned with the existing public API, project conventions, and supported PHP range.

## Package Overview
- **Package**: `drago-ex/commerce`
- **Type**: library
- **Purpose**: Simple shopping cart.
- **PHP**: >=8.3 <9
- **Runtime dependencies**: ext-intl (*), latte/latte (^3.1), nette/application (^3.2), nette/di (^3.0), nette/mail (^4.0), drago-ex/application (^4.0), drago-ex/database (^5.0), drago-ex/form (^3.0), brick/money (^0.15), brick/postcode (^0.6), nepada/phone-number-input (^1.0), tracy/tracy (^2.10), brick/math (^1.0), brick/phonenumber (^0.8.0), dibi/dibi (^5.1)
- **License**: MIT

## Repository Layout
- `migrations/`: database migrations.
- `src/`: package source code.
- `tests/`: automated tests.
- `.github/`: CI workflows; keep their PHP versions and commands consistent with Composer and the package scripts.
- `composer.json`: package metadata, dependency constraints, autoloading, and available Composer scripts.

## Key Parts
- Checkout: `Service\OrderService::place()` writes the order in one transaction; `Domain\Order\StockReservation` decrements stock atomically and must run inside that transaction.
- Prices: `CheckoutPricing` reprices the session cart before an order; `UI\Order\SummaryOrderControl` binds the confirmation form to an order fingerprint and a one-time token.
- `Service\ShoppingCartSession` stores domain objects in the session and drops items it cannot restore.
- `OrderSummary` is passed to event listeners (`OrderPlaced`); changing its property types is a breaking change. Its amounts and `OrderProduct::$unitPrice` are exact decimal strings (`(string) $money->getAmount()`, "150.00"), saved into the DECIMAL columns without a float on the way (user, 2026-10-31: entities keep `float` for forms and the grid, Dibi returns a DECIMAL as float only when the string survives the conversion, and all arithmetic is done in Money).
- Money (brick/money 0.15, brick/math 1.0): `Money::of()` takes no floats, so amounts from the database become Money only through `Commerce::money()` (`Commerce::decimal()` inside); a product percentage discount only through `Commerce::percentOff()` (exact `× (100 − p) / 100`), a code's percentage through `RationalMoney`, both rounded `RoundingMode::HalfUp` once; `Commerce::formatMoney()` formats with intl `NumberFormatter::formatCurrency()` (formatWith() no longer exists).
- The country of a customer is an ISO 3166-1 alpha-2 code (`customers.country CHAR(2)`), chosen in a select of the `countries` option (default `Commerce::DefaultCountries` = CZ, SK; `mergeDefaults(false)`, so a configured list replaces it), preselected by GeoLite2 when that country is allowed; names come from intl `Commerce::countryName()` (`Locale::getDisplayRegion()`) in the `lang` of the presenter (filter `|country` of `BaseTemplate`, the email uses `$lang`), select items are wrapped in `Html::el()->setText()` so the translator leaves them. `validatePostCode` (default true, replaced `postCodeOnRegionPhone`, user 2026-10-31) formats the postal code with brick/postcode for the chosen country, not the phone region; a country brick/postcode does not know keeps the postal code as written.
- Order states: `Domain\Order\OrderStatus` (BackedEnum, `next()`/`canChangeTo()`: new → processing → shipped → completed, cancel until shipped) in `OrderEntity::$status` (the first entity of `orders`; `OrderSummary` stays the insert DTO), payment apart in `paid_at` (user, 2026-10-31). `Service\OrderStatusService::change()` locks the order (`OrderRepository::getForUpdate()`, SELECT … FOR UPDATE) in `Table::transaction()`, a cancellation runs `StockReservation::release()` (same row order as `reserve()`) and `DiscountCodeRepository::decrementUsage()`; `OrderStatusChanged` is dispatched after the commit. Tests join a transaction with `transaction()` and roll back by throwing (a manual `begin()` inside `transaction()` is refused by Dibi). Returns and claims after shipping are not modelled.
- Database tests need the `COMMERCE_DB_*` variables and the SQL from `migrations/` and `migrations-demo/`.

## Dependencies

Composer constraints describe the package contract. Before changing integration behavior, inspect the source and the actual call sites in this package; do not infer runtime usage solely from dependency declarations.
Development dependencies include nette/tester (^2.5), phpstan/phpstan-nette (^2.0), geoip2/geoip2 (^2.12).

## Essential Commands

```bash
composer tester                 # tester tests -s
composer phpstan                # phpstan analyse
```

## Conventions & Rules

- Preserve backward compatibility unless the task explicitly permits a breaking change. Check callers and public signatures before changing behavior.
- Follow the existing PHP style: strict types, explicit parameter and return types, tabs for indentation, and single-quoted strings where practical. Match nearby code when a convention is unclear.
- Keep package-specific configuration, resources, and migrations in their established directories. Do not edit generated or consuming-project files without confirming they are part of this repository’s intended output.
- Add or update focused tests for behavioral changes when this package has a runnable test setup. Do not invent test commands; use only the scripts and configuration present here.
- Update README examples and Composer metadata when a public API or supported requirement changes.
