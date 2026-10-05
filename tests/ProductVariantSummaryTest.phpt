<?php

declare(strict_types=1);

namespace Drago\Commerce\Tests;

use Brick\Money\Money;
use Drago\Commerce\Commerce;
use Drago\Commerce\Domain\Product\ProductEntity;
use Drago\Commerce\Domain\Product\ProductVariantSummary;
use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$commerce = new Commerce(['currency' => 'CZK', 'moneyFormat' => 'cs_CZ']);

$product = new ProductEntity;
$product->id = 1;
$product->price = 1000.0;
$product->discount = 10;
$product->stock = 0;


// Variants with their own prices only: the cheapest own price wins and the
// product discount is irrelevant.
$summary = new ProductVariantSummary;
$summary->add(3, 800.0);
$summary->add(0, 700.0);
$summary->add(2, 950.0);

Assert::same(3, $summary->count);
Assert::same(5, $summary->stock);
Assert::true($summary->inStock());
Assert::false($summary->inheritsPrice);
Assert::true($summary->getLowestPrice($product, $commerce)->isEqualTo(Money::of(700, 'CZK')));


// A variant inheriting the product price is sold at the discounted product price.
$summary = new ProductVariantSummary;
$summary->add(1, null);
$summary->add(1, 950.0);

Assert::true($summary->inheritsPrice);
Assert::true($summary->getLowestPrice($product, $commerce)->isEqualTo(Money::of(900, 'CZK')));


// The inherited price is not the lowest when an own price is cheaper still.
$summary = new ProductVariantSummary;
$summary->add(1, null);
$summary->add(1, 850.0);

Assert::true($summary->getLowestPrice($product, $commerce)->isEqualTo(Money::of(850, 'CZK')));


// Everything sold out.
$summary = new ProductVariantSummary;
$summary->add(0, null);

Assert::false($summary->inStock());
