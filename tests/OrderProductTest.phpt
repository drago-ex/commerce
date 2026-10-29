<?php declare(strict_types=1);

namespace Drago\Commerce\Tests;

use Brick\Math\BigInteger;
use Brick\Money\Money;
use Drago\Commerce\Domain\Order\OrderProduct;
use Drago\Commerce\Domain\Product\Product;
use Drago\Commerce\Domain\Product\ProductCart;
use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$firstVariantLine = new OrderProduct(
	orderId: 42,
	productId: 6,
	variantId: 24,
	amount: 2,
	unitPrice: 490.0,
);
$secondVariantLine = new OrderProduct(
	orderId: 42,
	productId: 6,
	variantId: 28,
	amount: 1,
	unitPrice: 490.0,
);
$nonVariantLine = new OrderProduct(
	orderId: 42,
	productId: 7,
	variantId: null,
	amount: 1,
	unitPrice: 1250.0,
);

Assert::same([
	'orderId' => 42,
	'productId' => 6,
	'variantId' => 24,
	'amount' => 2,
	'unitPrice' => 490.0,
	'productName' => '',
	'variantLabel' => null,
], (array) $firstVariantLine);
Assert::same(28, ((array) $secondVariantLine)['variantId']);
Assert::hasKey('variantId', (array) $nonVariantLine);
Assert::null(((array) $nonVariantLine)['variantId']);

$variantCartItem = new ProductCart(
	new Product(id: 6, name: 'Notebook', price: Money::of(32490, 'CZK')),
	BigInteger::one(),
	variantId: 31,
);
$persistedVariantLine = OrderProduct::fromCartItem(43, $variantCartItem);
Assert::same(32490.0, $persistedVariantLine->unitPrice);
Assert::same(31, $persistedVariantLine->variantId);

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
Assert::same('Pánské tričko Classic', $labelledLine->productName);
Assert::same('Barva: Bílá, Velikost: L', $labelledLine->variantLabel);
Assert::same('Notebook', $persistedVariantLine->productName);
Assert::null($persistedVariantLine->variantLabel);
