<?php

declare(strict_types=1);

namespace Drago\Commerce\Tests;

use Brick\Math\BigInteger;
use Brick\Money\Money;
use Dibi\Connection;
use Drago\Commerce\Domain\Order\ItemUnavailableException;
use Drago\Commerce\Domain\Order\OutOfStockException;
use Drago\Commerce\Domain\Order\StockReservation;
use Drago\Commerce\Domain\Product\Product;
use Drago\Commerce\Domain\Product\ProductCart;
use Drago\Commerce\Domain\Product\ProductRepository;
use Drago\Commerce\Domain\Product\ProductVariantRepository;
use Tester\Assert;
use Tester\Environment;

require __DIR__ . '/bootstrap.php';

$host = getenv('COMMERCE_DB_HOST');
if (!is_string($host) || $host === '') {
	Environment::skip('Set COMMERCE_DB_HOST and import migrations to run database integration tests.');
}

$connectionConfig = [
	'driver' => 'mysqli',
	'host' => $host,
	'port' => (int) (getenv('COMMERCE_DB_PORT') ?: 3306),
	'username' => getenv('COMMERCE_DB_USER') ?: 'root',
	'password' => getenv('COMMERCE_DB_PASSWORD') ?: '',
	'database' => getenv('COMMERCE_DB_NAME') ?: 'test',
	'charset' => 'utf8mb4',
];
$connection = new Connection($connectionConfig);

$line = static fn(int $productId, int $amount, ?int $variantId = null): ProductCart => new ProductCart(
	new Product(id: $productId, name: "Product $productId", price: Money::of(100, 'CZK')),
	BigInteger::of($amount),
	$variantId,
);

$variantStock = static fn(int $id): int => (int) $connection
	->query('SELECT stock FROM product_variants WHERE id = %i', $id)
	->fetchSingle();

$productStock = static fn(int $id): int => (int) $connection
	->query('SELECT stock FROM products WHERE id = %i', $id)
	->fetchSingle();

// Seed: product 5 has variants 21 (18 pcs), 22 (12 pcs) and 23 (5 pcs).
$connection->begin();

try {
	$reservation = new StockReservation(
		new ProductRepository($connection),
		new ProductVariantRepository($connection),
	);

	// Several variants of one product are reserved from their own stock.
	$reservation->reserve([$line(5, 2, 21), $line(5, 3, 23)]);
	Assert::same(16, $variantStock(21));
	Assert::same(2, $variantStock(23));
	Assert::same(30, $productStock(5));

	// More than what is left.
	$e = Assert::exception(
		fn() => $reservation->reserve([$line(5, 3, 23)]),
		OutOfStockException::class,
	);
	Assert::same('Product 5', $e->productName);
	Assert::same(2, $variantStock(23));

	// A product with variants cannot be ordered without choosing one.
	$e = Assert::exception(
		fn() => $reservation->reserve([$line(5, 1)]),
		ItemUnavailableException::class,
	);
	Assert::same('Product 5', $e->productName);
	Assert::same(30, $productStock(5));

	// A variant of another product, and a variant that does not exist.
	Assert::exception(fn() => $reservation->reserve([$line(1, 1, 21)]), ItemUnavailableException::class);
	Assert::exception(fn() => $reservation->reserve([$line(5, 1, 999999)]), ItemUnavailableException::class);
	Assert::same(16, $variantStock(21));

	// An inactive variant.
	$connection->query('UPDATE product_variants SET active = 0 WHERE id = %i', 22);
	Assert::exception(fn() => $reservation->reserve([$line(5, 1, 22)]), ItemUnavailableException::class);
	Assert::same(12, $variantStock(22));

	// An invalid quantity.
	Assert::exception(fn() => $reservation->reserve([$line(5, 0, 21)]), ItemUnavailableException::class);

	// A product without variants uses its own stock.
	$connection->query(
		'INSERT INTO products (category, name, description, discount, price, photo, active, stock) VALUES (%i, %s, %s, NULL, %i, %s, %i, %i)',
		1,
		'Reservation test ' . uniqid(),
		'',
		100,
		'',
		1,
		5,
	);
	$plainId = $connection->getInsertId();

	$reservation->reserve([$line($plainId, 4)]);
	Assert::same(1, $productStock($plainId));

	Assert::exception(fn() => $reservation->reserve([$line($plainId, 2)]), OutOfStockException::class);
	Assert::same(1, $productStock($plainId));

	// An inactive product, and a product that does not exist.
	$connection->query('UPDATE products SET active = 0 WHERE id = %i', $plainId);
	Assert::exception(fn() => $reservation->reserve([$line($plainId, 1)]), ItemUnavailableException::class);
	Assert::exception(fn() => $reservation->reserve([$line(999999, 1)]), ItemUnavailableException::class);
} finally {
	$connection->rollback();
	$connection->disconnect();
}

// Nothing of the above survives the rollback.
$verification = new Connection($connectionConfig);
Assert::same(18, (int) $verification->query('SELECT stock FROM product_variants WHERE id = %i', 21)->fetchSingle());
Assert::same(5, (int) $verification->query('SELECT stock FROM product_variants WHERE id = %i', 23)->fetchSingle());
Assert::same(1, (int) $verification->query('SELECT active FROM product_variants WHERE id = %i', 22)->fetchSingle());
$verification->disconnect();
