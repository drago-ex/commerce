<?php

declare(strict_types=1);

namespace Drago\Commerce\Tests;

use Brick\Money\Money;
use Drago\Commerce\Domain\Product\Product;
use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$product = new Product(
	id: 1,
	name: 'Test Product',
	price: Money::of(100, 'CZK'),
);

// No discount
Assert::false($product->hasDiscount());
Assert::null($product->getDiscount());
Assert::true($product->getDiscountedPrice()->isEqualTo(Money::of(100, 'CZK')));
Assert::true($product->getDiscountAmount()->isEqualTo(Money::of(0, 'CZK')));

// 20% discount
$product->setDiscount(20);
Assert::true($product->hasDiscount());
Assert::same(20, $product->getDiscount());
Assert::true($product->getDiscountedPrice()->isEqualTo(Money::of(80, 'CZK')));
Assert::true($product->getDiscountAmount()->isEqualTo(Money::of(20, 'CZK')));

// 0% discount
$product->setDiscount(0);
Assert::false($product->hasDiscount());
Assert::true($product->getDiscountedPrice()->isEqualTo(Money::of(100, 'CZK')));
Assert::true($product->getDiscountAmount()->isEqualTo(Money::of(0, 'CZK')));

// 100% discount
$product->setDiscount(100);
Assert::true($product->hasDiscount());
Assert::true($product->getDiscountedPrice()->isEqualTo(Money::of(0, 'CZK')));
Assert::true($product->getDiscountAmount()->isEqualTo(Money::of(100, 'CZK')));
