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
$item = new ProductCart(
	new Product(
		id: 4,
		name: 'Herní notebook ASUS ROG Strix',
		price: Money::of(32490, 'CZK'),
	),
	BigInteger::one(),
	variantId: 17,
);

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

try {
	// 1. Successful placement: all order data is persisted and variant stock is decremented.
	$stockBefore = (int) $connection
		->query('SELECT stock FROM product_variants WHERE id = %i', 17)
		->fetchSingle();

	$placement = $orderService->place(
		$customer,
		$carrier,
		$payment,
		[$item],
		Money::of(32490, 'CZK'),
		Money::zero('CZK'),
		Money::of(32640, 'CZK'),
		null,
	);

	$successOrderId = $placement->orderId;
	$successCustomerId = $placement->orderSummary->customer_id;

	Assert::true($placement->orderId > 0);
	Assert::same($successCustomerId, $placement->orderSummary->customer_id);
	Assert::same(32490.0, $placement->orderSummary->subtotal_price);
	Assert::same(32640.0, $placement->orderSummary->total_price);
	Assert::same(
		$stockBefore - 1,
		(int) $connection->query(
			'SELECT stock FROM product_variants WHERE id = %i',
			17,
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
	$connection->query('UPDATE product_variants SET stock = stock + 1 WHERE id = %i', 17);
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
		new Product(
			id: 4,
			name: 'Herní notebook ASUS ROG Strix',
			price: Money::of(52490, 'CZK'),
		),
		BigInteger::one(),
		variantId: 20,
	);

	Assert::same(
		0,
		(int) $connection->query('SELECT stock FROM product_variants WHERE id = %i', 20)->fetchSingle(),
	);

	Assert::exception(
		fn() => $orderService->place(
			$outOfStockCustomer,
			$carrier,
			$payment,
			[$outOfStockItem],
			Money::of(52490, 'CZK'),
			Money::zero('CZK'),
			Money::of(52640, 'CZK'),
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
		(int) $connection->query('SELECT stock FROM product_variants WHERE id = %i', 20)->fetchSingle(),
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


		public function consume(): bool
		{
			return false;
		}
	};

	$rollbackOrderService = new OrderService(
		$orderRepository,
		$orderProductsRepository,
		$customerRepository,
		$stockReservation,
		$rejectingDiscountService,
	);

	$stockBeforeRollback = (int) $connection
		->query('SELECT stock FROM product_variants WHERE id = %i', 17)
		->fetchSingle();

	Assert::exception(
		fn() => $rollbackOrderService->place(
			$rollbackCustomer,
			$carrier,
			$payment,
			[$item],
			Money::of(32490, 'CZK'),
			Money::of(100, 'CZK'),
			Money::of(32540, 'CZK'),
			'TEST10',
		),
		OrderException::class,
	);

	Assert::same(
		$stockBeforeRollback,
		(int) $connection->query('SELECT stock FROM product_variants WHERE id = %i', 17)->fetchSingle(),
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
	$connection->disconnect();
}
