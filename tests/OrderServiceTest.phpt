<?php

declare(strict_types=1);

namespace Drago\Commerce\Tests;

use Brick\Math\BigInteger;
use Brick\Money\Money;
use Dibi\Connection;
use Drago\Commerce\Domain\Customer\Customer;
use Drago\Commerce\Domain\Customer\CustomerRepository;
use Drago\Commerce\Domain\Delivery\Carrier;
use Drago\Commerce\Domain\Delivery\Payment;
use Drago\Commerce\Domain\DiscountCode\DiscountCodeEntity;
use Drago\Commerce\Domain\DiscountCode\DiscountCodeRepository;
use Drago\Commerce\Domain\Order\OrderException;
use Drago\Commerce\Domain\Order\OrderProductRepository;
use Drago\Commerce\Domain\Order\OrderRepository;
use Drago\Commerce\Domain\Order\OutOfStockException;
use Drago\Commerce\Domain\Order\StockReservation;
use Drago\Commerce\Domain\Product\Product;
use Drago\Commerce\Domain\Product\ProductCart;
use Drago\Commerce\Domain\Product\ProductRepository;
use Drago\Commerce\Domain\Product\ProductVariantRepository;
use Drago\Commerce\Service\DiscountCodeService;
use Drago\Commerce\Service\OrderService;
use Nette\Http\Request;
use Nette\Http\Response;
use Nette\Http\Session;
use Nette\Http\UrlScript;
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
$session = new Session(
	new Request(new UrlScript('http://localhost/')),
	new Response,
);
$session->setExpiration('2 days');

$customer = new Customer(
	email: 'order-service-' . uniqid('', true) . '@example.com',
	phone: '+420777123456',
	name: 'Order',
	surname: 'Service',
	street: 'Testovací 1',
	city: 'Praha',
	postal_code: '11000',
	country: 'CZ',
	note: 'OrderService test',
);

$carrier = new Carrier(1, 'DHL', Money::of(150, 'CZK'));
$payment = new Payment(1, 'Platba kartou', Money::zero('CZK'));
$orderRepository = new OrderRepository($connection);
$orderProductsRepository = new OrderProductRepository($connection);
$customerRepository = new CustomerRepository($connection);
$stockReservation = new StockReservation(
	new ProductRepository($connection),
	new ProductVariantRepository($connection),
);
$discountCodeService = new DiscountCodeService(
	$session,
	new class extends DiscountCodeRepository {
		public function __construct()
		{
		}
	},
);
$orderService = new OrderService(
	$orderRepository,
	$orderProductsRepository,
	$customerRepository,
	$stockReservation,
	$discountCodeService,
);

$successOrderId = null;
$successCustomerId = null;
$successVariantId = 0;
$rollbackVariantId = 0;
$outOfStockVariantId = 0;

try {
	// Each variant is created by this test so it can run safely in parallel with other DB tests.
	$createVariant = static function (Connection $connection, int $stock): int {
		$sku = 'order-service-test-' . bin2hex(random_bytes(8));
		$connection->query(
			'INSERT INTO product_variants ([product_id], [sku], [price], [stock], [active]) VALUES (%i, %s, %f, %i, %i)',
			6,
			$sku,
			490,
			$stock,
			1,
		);

		return $connection->getInsertId();
	};

	$successVariantId = $createVariant($connection, 1);
	$rollbackVariantId = $createVariant($connection, 1);
	$outOfStockVariantId = $createVariant($connection, 0);

	$item = new ProductCart(
		new Product(id: 6, name: 'Pánské tričko Classic', price: Money::of(490, 'CZK')),
		BigInteger::one(),
		variantId: $successVariantId,
	);

	// 1. Successful placement.
	$stockBefore = (int) $connection
		->query('SELECT stock FROM product_variants WHERE id = %i', $successVariantId)
		->fetchSingle();

	$placement = $orderService->place(
		$customer,
		$carrier,
		$payment,
		[$item],
		Money::of(490, 'CZK'),
		Money::zero('CZK'),
		Money::of(640, 'CZK'),
		null,
	);

	$successOrderId = $placement->orderId;
	$successCustomerId = $placement->orderSummary->customer_id;

	Assert::true($placement->orderId > 0);
	Assert::same($successCustomerId, $placement->orderSummary->customer_id);
	Assert::same(490.0, $placement->orderSummary->subtotal_price);
	Assert::same(640.0, $placement->orderSummary->total_price);
	Assert::same(
		$stockBefore - 1,
		(int) $connection->query(
			'SELECT stock FROM product_variants WHERE id = %i',
			$successVariantId,
		)->fetchSingle(),
	);
	Assert::same(
		1,
		(int) $connection->query(
			'SELECT COUNT(*) FROM orders WHERE id = %i',
			$successOrderId,
		)->fetchSingle(),
	);
	Assert::same(
		1,
		(int) $connection->query(
			'SELECT COUNT(*) FROM orders_products WHERE order_id = %i',
			$successOrderId,
		)->fetchSingle(),
	);
	Assert::same(
		'Pánské tričko Classic',
		$connection->query('SELECT product_name FROM orders_products WHERE order_id = %i', $successOrderId)->fetchSingle(),
	);
	Assert::same(
		1,
		(int) $connection->query(
			'SELECT COUNT(*) FROM customers WHERE id = %i',
			$successCustomerId,
		)->fetchSingle(),
	);

	// Clean up the committed order so this test is repeatable.
	$connection->query('DELETE FROM orders_products WHERE order_id = %i', $successOrderId);
	$connection->query('DELETE FROM orders WHERE id = %i', $successOrderId);
	$connection->query('DELETE FROM customers WHERE id = %i', $successCustomerId);
	$connection->query('UPDATE product_variants SET stock = stock + 1 WHERE id = %i', $successVariantId);
	$successOrderId = null;
	$successCustomerId = null;

	// 2. Out-of-stock: the customer/order writes must be rolled back.
	$outOfStockCustomer = new Customer(
		email: 'order-service-out-of-stock-' . uniqid('', true) . '@example.com',
		phone: '+420777123456',
		name: 'Out',
		surname: 'OfStock',
		street: 'Testovací 2',
		city: 'Praha',
		postal_code: '11000',
		country: 'CZ',
		note: null,
	);
	$outOfStockItem = new ProductCart(
		new Product(id: 6, name: 'Pánské tričko Classic', price: Money::of(490, 'CZK')),
		BigInteger::one(),
		variantId: $outOfStockVariantId,
	);

	Assert::same(
		0,
		(int) $connection->query('SELECT stock FROM product_variants WHERE id = %i', $outOfStockVariantId)->fetchSingle(),
	);

	Assert::exception(
		fn() => $orderService->place(
			$outOfStockCustomer,
			$carrier,
			$payment,
			[$outOfStockItem],
			Money::of(490, 'CZK'),
			Money::zero('CZK'),
			Money::of(640, 'CZK'),
			null,
		),
		OutOfStockException::class,
	);

	Assert::same(
		0,
		(int) $connection->query(
			'SELECT COUNT(*) FROM customers WHERE email = %s',
			$outOfStockCustomer->email,
		)->fetchSingle(),
	);
	Assert::same(
		0,
		(int) $connection->query('SELECT stock FROM product_variants WHERE id = %i', $outOfStockVariantId)->fetchSingle(),
	);

	// 3. If discount consumption fails after stock reservation, everything rolls back.
	$rollbackCustomer = new Customer(
		email: 'order-service-discount-' . uniqid('', true) . '@example.com',
		phone: '+420777123456',
		name: 'Discount',
		surname: 'Rollback',
		street: 'Testovací 3',
		city: 'Praha',
		postal_code: '11000',
		country: 'CZ',
		note: null,
	);

	$rejectingDiscountService = new class extends DiscountCodeService {
		public function __construct()
		{
		}


		public function consume(?DiscountCodeEntity $discountCode = null): bool
		{
			return false;
		}
	};

	$rejectedCode = new DiscountCodeEntity;
	$rejectedCode->id = 1;
	$rejectedCode->code = 'TEST10';
	$rejectedCode->type = 'fixed';
	$rejectedCode->value = 100;
	$rejectedCode->valid_from = null;
	$rejectedCode->valid_to = null;
	$rejectedCode->usage_limit = null;
	$rejectedCode->used_count = 0;
	$rejectedCode->minimum_order_amount = null;
	$rejectedCode->active = 1;

	$rollbackOrderService = new OrderService(
		$orderRepository,
		$orderProductsRepository,
		$customerRepository,
		$stockReservation,
		$rejectingDiscountService,
	);

	$rollbackItem = new ProductCart(
		new Product(id: 6, name: 'Pánské tričko Classic', price: Money::of(490, 'CZK')),
		BigInteger::one(),
		variantId: $rollbackVariantId,
	);

	$stockBeforeRollback = (int) $connection
		->query('SELECT stock FROM product_variants WHERE id = %i', $rollbackVariantId)
		->fetchSingle();

	Assert::exception(
		fn() => $rollbackOrderService->place(
			$rollbackCustomer,
			$carrier,
			$payment,
			[$rollbackItem],
			Money::of(490, 'CZK'),
			Money::of(100, 'CZK'),
			Money::of(540, 'CZK'),
			$rejectedCode,
		),
		OrderException::class,
	);

	Assert::same(
		$stockBeforeRollback,
		(int) $connection->query('SELECT stock FROM product_variants WHERE id = %i', $rollbackVariantId)->fetchSingle(),
	);
	Assert::same(
		0,
		(int) $connection->query(
			'SELECT COUNT(*) FROM customers WHERE email = %s',
			$rollbackCustomer->email,
		)->fetchSingle(),
	);
} finally {
	// Safety cleanup if the success assertions failed before normal cleanup.
	if ($successOrderId !== null) {
		$connection->query('DELETE FROM orders_products WHERE order_id = %i', $successOrderId);
		$connection->query('DELETE FROM orders WHERE id = %i', $successOrderId);
	}
	if ($successCustomerId !== null) {
		$connection->query('DELETE FROM customers WHERE id = %i', $successCustomerId);
	}
	$connection->query(
		'DELETE FROM product_variants WHERE id IN (%i, %i, %i)',
		$successVariantId,
		$rollbackVariantId,
		$outOfStockVariantId,
	);
	$connection->disconnect();
}
