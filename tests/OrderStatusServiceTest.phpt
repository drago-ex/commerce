<?php declare(strict_types=1);

namespace Drago\Commerce\Tests;

use Dibi\Connection;
use Drago\Commerce\Domain\DiscountCode\DiscountCodeRepository;
use Drago\Commerce\Domain\Order\OrderEntity;
use Drago\Commerce\Domain\Order\OrderProductRepository;
use Drago\Commerce\Domain\Order\OrderRepository;
use Drago\Commerce\Domain\Order\OrderStatus;
use Drago\Commerce\Domain\Order\OrderStatusException;
use Drago\Commerce\Domain\Order\StockReservation;
use Drago\Commerce\Domain\Product\ProductRepository;
use Drago\Commerce\Domain\Product\ProductVariantRepository;
use Drago\Commerce\Event\EventDispatcher;
use Drago\Commerce\Event\OrderStatusChanged;
use Drago\Commerce\Service\OrderStatusService;
use RuntimeException;
use Tester\Assert;
use Tester\Environment;

require __DIR__ . '/bootstrap.php';

$host = getenv('COMMERCE_DB_HOST');
if (!is_string($host) || $host === '') {
	Environment::skip('Set COMMERCE_DB_HOST and import migrations to run database integration tests.');
}

$connection = new Connection([
	'driver' => 'mysqli',
	'host' => $host,
	'port' => (int) (getenv('COMMERCE_DB_PORT') ?: 3306),
	'username' => getenv('COMMERCE_DB_USER') ?: 'root',
	'password' => getenv('COMMERCE_DB_PASSWORD') ?: '',
	'database' => getenv('COMMERCE_DB_NAME') ?: 'test',
	'charset' => 'utf8mb4',
]);

$value = static fn(string $sql, mixed ...$args): mixed => $connection->query($sql, ...$args)->fetchSingle();

/** @var list<OrderStatusChanged> $events */
$events = [];
$dispatcher = new EventDispatcher;
$dispatcher->addListener(OrderStatusChanged::class, function (OrderStatusChanged $event) use (&$events): void {
	$events[] = $event;
});

$orderRepository = new OrderRepository($connection);
$service = new OrderStatusService(
	$orderRepository,
	new OrderProductRepository($connection),
	new StockReservation(new ProductRepository($connection), new ProductVariantRepository($connection)),
	new DiscountCodeRepository($connection),
	$dispatcher,
);

// everything runs in one transaction, rolled back at the end; the service joins it
try {
	$connection->transaction(function () use ($connection, $value, $service, $orderRepository, &$events): void {
		// Seed: product 1 (no variants), variant 21 of product 5, discount code TEST10, customer 1, carrier 1, payment 1.
		$productStock = (int) $value('SELECT stock FROM products WHERE id = 1');
		$variantStock = (int) $value('SELECT stock FROM product_variants WHERE id = 21');
		$connection->query('UPDATE discount_codes SET used_count = 1 WHERE code = %s', 'TEST10');

		$connection->query('INSERT INTO orders %v', [
			'customer_id' => 1,
			'carrier_id' => 1,
			'payment_id' => 1,
			'carrier_price' => '0.00',
			'payment_price' => '0.00',
			'subtotal_price' => '100.00',
			'total_price' => '100.00',
			'discount_code' => 'TEST10',
			'currency' => 'CZK',
		]);
		$orderId = $connection->getInsertId();
		$connection->query('INSERT INTO orders_products %ex', [
			['order_id' => $orderId, 'product_id' => 1, 'variant_id' => null, 'amount' => 2, 'unit_price' => '50.00'],
			['order_id' => $orderId, 'product_id' => 5, 'variant_id' => 21, 'amount' => 3, 'unit_price' => '10.00'],
		]);

		$order = $orderRepository->table->get($orderId)->record();
		Assert::type(OrderEntity::class, $order);
		Assert::same(OrderStatus::New, $order->status);
		Assert::null($order->paidAt);

		// allowed changes, each dispatched with the previous state
		$service->change($orderId, OrderStatus::Processing);
		Assert::same(OrderStatus::Processing, $orderRepository->table->get($orderId)->record()?->status);
		Assert::count(1, $events);
		Assert::same(OrderStatus::New, $events[0]->previous);
		Assert::same(OrderStatus::Processing, $events[0]->status);

		// a payment is recorded once
		Assert::true($service->markPaid($orderId));
		Assert::false($service->markPaid($orderId));
		Assert::notNull($orderRepository->table->get($orderId)->record()?->paidAt);

		// cancelling returns the items to stock and the use of the discount code
		$service->change($orderId, OrderStatus::Cancelled);
		Assert::same(OrderStatus::Cancelled, $orderRepository->table->get($orderId)->record()?->status);
		Assert::same($productStock + 2, (int) $value('SELECT stock FROM products WHERE id = 1'));
		Assert::same($variantStock + 3, (int) $value('SELECT stock FROM product_variants WHERE id = 21'));
		Assert::same(0, (int) $value('SELECT used_count FROM discount_codes WHERE code = %s', 'TEST10'));

		// a cancelled order cannot change, and nothing is returned twice
		Assert::exception(
			fn() => $service->change($orderId, OrderStatus::New),
			OrderStatusException::class,
			"The order $orderId cannot change from 'cancelled' to 'new'.",
		);
		Assert::same($productStock + 2, (int) $value('SELECT stock FROM products WHERE id = 1'));
		Assert::count(2, $events);

		Assert::exception(
			fn() => $service->change(999999, OrderStatus::Processing),
			OrderStatusException::class,
			'The order 999999 does not exist.',
		);

		throw new RuntimeException('rollback');
	});
} catch (RuntimeException $e) {
	Assert::same('rollback', $e->getMessage());
} finally {
	$connection->disconnect();
}
