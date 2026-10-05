<?php

declare(strict_types=1);

namespace Drago\Commerce\Tests;

use Brick\Math\BigInteger;
use Brick\Money\Money;
use DateTimeImmutable;
use Dibi\Connection;
use Dibi\ForeignKeyConstraintViolationException;
use Dibi\Row;
use Dibi\UniqueConstraintViolationException;
use Drago\Commerce\Domain\Order\OrderProductRepository;
use Drago\Commerce\Domain\Order\OrderRepository;
use Drago\Commerce\Domain\Product\Product;
use Drago\Commerce\Domain\Product\ProductCart;
use Drago\Commerce\Domain\Product\ProductImageRepository;
use Drago\Commerce\Domain\Product\ProductRepository;
use Drago\Commerce\Domain\Product\ProductVariantRepository;
use Drago\Commerce\Domain\Order\OrderProduct;
use Drago\Commerce\Domain\Order\OrderSummary;
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
$orderId = 0;

$connection->begin();

try {
	$productRepository = new ProductRepository($connection);
	$productImageRepository = new ProductImageRepository($connection);
	$variantRepository = new ProductVariantRepository($connection);
	$orderRepository = new OrderRepository($connection);
	$orderProductsRepository = new OrderProductRepository($connection);

	$product = $productRepository->getOne(4);
	Assert::notNull($product);
	Assert::same('Herní notebook ASUS ROG Strix', $product->name);
	$connection->query(
		'INSERT INTO [product_images] ([product_id], [image], [position]) VALUES (%i, %s, %i), (%i, %s, %i)',
		3,
		'/images/notebook-side.jpg',
		2,
		3,
		'/images/notebook-back.jpg',
		1,
	);
	Assert::same([
		'/images/notebook-back.jpg',
		'/images/notebook-side.jpg',
	], $productImageRepository->getForProduct(3));

	$variant = $variantRepository->getOne(17);
	Assert::notNull($variant);
	Assert::same(4, $variant->product_id);
	Assert::same([
		'Procesor: AMD Ryzen 7 8845HS',
		'Grafická karta: NVIDIA GeForce RTX 5060 8GB',
		'Operační systém: Bez operačního systému',
	], $variantRepository->getLabels(17));

	Assert::true($variantRepository->hasActive(4));
	Assert::false($variantRepository->hasActive(999999));

	$attributes = $variantRepository->getAttributesForProduct(4);
	Assert::same(
		['Procesor', 'Grafická karta', 'Operační systém'],
		array_column($attributes[17], 'attribute'),
	);
	Assert::same(
		$variantRepository->getAttributeValueIds(17),
		array_column($attributes[17], 'valueId'),
	);

	$summaries = $variantRepository->getSummaries();
	Assert::same(5, $summaries[4]->count);
	Assert::same(19, $summaries[4]->stock);
	Assert::true($summaries[4]->inheritsPrice);
	Assert::true($summaries[4]->inStock());

	Assert::true($productRepository->decrementStock(4, 2));
	Assert::false($productRepository->decrementStock(4, 14));
	Assert::same(13, (int) $connection->query('SELECT stock FROM products WHERE id = %i', 4)->fetchSingle());

	Assert::true($variantRepository->decrementStock(17, 3));
	Assert::false($variantRepository->decrementStock(17, 3));
	Assert::same(2, (int) $connection->query('SELECT stock FROM product_variants WHERE id = %i', 17)->fetchSingle());

	$orderRepository->save((array) new OrderSummary(
		customer_id: 1,
		carrier_id: 1,
		payment_id: 1,
		carrier_price: 150,
		payment_price: 0,
		subtotal_price: 107970,
		total_price: 108120,
		discount_code: null,
		discount_amount: 0,
		created_at: new DateTimeImmutable,
	));
	$orderId = $orderRepository->getInsertId();

	$firstVariant = new ProductCart(
		new Product(id: 4, name: 'Herní notebook ASUS ROG Strix', price: Money::of(32490, 'CZK')),
		BigInteger::one(),
		variantId: 17,
	);
	$secondVariant = new ProductCart(
		new Product(id: 4, name: 'Herní notebook ASUS ROG Strix', price: Money::of(42990, 'CZK')),
		BigInteger::one(),
		variantId: 18,
	);
	$regularProduct = new ProductCart(
		new Product(id: 4, name: 'Herní notebook ASUS ROG Strix', price: Money::of(32490, 'CZK')),
		BigInteger::one(),
	);

	foreach ([$firstVariant, $secondVariant, $regularProduct] as $cartItem) {
		$orderProductsRepository
			->insert((array) OrderProduct::fromCartItem($orderId, $cartItem))
			->execute();
	}

	$lines = $connection->query(
		'SELECT variant_id, unit_price FROM orders_products WHERE order_id = %i ORDER BY variant_key',
		$orderId,
	)->fetchAll();
	Assert::count(3, $lines);
	Assert::same([null, 17, 18], array_map(
		static fn(Row $line): ?int => $line->variant_id === null ? null : (int) $line->variant_id,
		$lines,
	));
	Assert::same([32490.0, 32490.0, 42990.0], array_map(
		static fn(Row $line): float => (float) $line->unit_price,
		$lines,
	));

	Assert::exception(
		fn() => $orderProductsRepository->insert([
			'order_id' => $orderId,
			'product_id' => 4,
			'variant_id' => 17,
			'amount' => 1,
			'unit_price' => 32490,
		])->execute(),
		UniqueConstraintViolationException::class,
	);

	Assert::exception(
		fn() => $orderProductsRepository->insert([
			'order_id' => $orderId,
			'product_id' => 2,
			'variant_id' => 17,
			'amount' => 1,
			'unit_price' => 1,
		])->execute(),
		ForeignKeyConstraintViolationException::class,
	);
} finally {
	$connection->rollback();
	$connection->disconnect();
}

$verificationConnection = new Connection($connectionConfig);
Assert::same(15, (int) $verificationConnection->query('SELECT stock FROM products WHERE id = %i', 4)->fetchSingle());
Assert::same(5, (int) $verificationConnection->query('SELECT stock FROM product_variants WHERE id = %i', 17)->fetchSingle());
Assert::same(0, (int) $verificationConnection->query('SELECT COUNT(*) FROM orders WHERE id = %i', $orderId)->fetchSingle());
$verificationConnection->disconnect();
