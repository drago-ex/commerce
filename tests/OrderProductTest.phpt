<?php

declare(strict_types=1);

namespace Drago\Commerce\Tests;

use Brick\Math\BigInteger;
use Brick\Money\Money;
use Drago\Commerce\Domain\Order\OrderProduct;
use Drago\Commerce\Domain\Product\Product;
use Drago\Commerce\Domain\Product\ProductCart;
use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$firstVariantLine = new OrderProduct(
	order_id: 42,
	product_id: 6,
	variant_id: 24,
	amount: 2,
	unit_price: 490.0,
);
$secondVariantLine = new OrderProduct(
	order_id: 42,
	product_id: 6,
	variant_id: 28,
	amount: 1,
	unit_price: 490.0,
);
$nonVariantLine = new OrderProduct(
	order_id: 42,
	product_id: 7,
	variant_id: null,
	amount: 1,
	unit_price: 1250.0,
);

Assert::same([
	'order_id' => 42,
	'product_id' => 6,
	'variant_id' => 24,
	'amount' => 2,
	'unit_price' => 490.0,
	'product_name' => '',
	'variant_label' => null,
], (array) $firstVariantLine);
Assert::same(28, ((array) $secondVariantLine)['variant_id']);
Assert::hasKey('variant_id', (array) $nonVariantLine);
Assert::null(((array) $nonVariantLine)['variant_id']);

$variantCartItem = new ProductCart(
	new Product(id: 6, name: 'Notebook', price: Money::of(32490, 'CZK')),
	BigInteger::one(),
	variantId: 31,
);
$persistedVariantLine = OrderProduct::fromCartItem(43, $variantCartItem);
Assert::same(32490.0, $persistedVariantLine->unit_price);
Assert::same(31, $persistedVariantLine->variant_id);

// The name and variant label are copied from the cart line, so the order keeps reading the same later.
$labelledLine = OrderProduct::fromCartItem(
	44,
	new ProductCart(
		new Product(id: 6, name: 'Pánské tričko Classic', price: Money::of(490, 'CZK')),
		BigInteger::one(),
		variantId: 25,
		variantLabel: 'Barva: Bílá, Velikost: L',
	),
);
Assert::same('Pánské tričko Classic', $labelledLine->product_name);
Assert::same('Barva: Bílá, Velikost: L', $labelledLine->variant_label);
Assert::same('Notebook', $persistedVariantLine->product_name);
Assert::null($persistedVariantLine->variant_label);
