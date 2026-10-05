<?php

declare(strict_types=1);

namespace Drago\Commerce\Tests;

use Brick\Math\BigInteger;
use Brick\Money\Money;
use Drago\Commerce\Commerce;
use Drago\Commerce\Domain\Customer\Customer;
use Drago\Commerce\Domain\Delivery\Carrier;
use Drago\Commerce\Domain\Delivery\Payment;
use Drago\Commerce\Domain\DiscountCode\DiscountCodeEntity;
use Drago\Commerce\Domain\DiscountCode\DiscountCodeRepository;
use Drago\Commerce\Domain\Product\Product;
use Drago\Commerce\Domain\Product\ProductCart;
use Drago\Commerce\Event\CartItemChanged;
use Drago\Commerce\Event\CartItemRemoved;
use Drago\Commerce\Event\EventDispatcher;
use Drago\Commerce\Event\OrderPlaced;
use Drago\Commerce\Event\ProductAddedToCart;
use Drago\Commerce\EventListener\OrderLoggerListener;
use Drago\Commerce\Service\DiscountCodeService;
use Drago\Commerce\Service\ShoppingCartSession;
use Drago\Commerce\UI\Order\OrderSummary;
use Nette\Http\Request;
use Nette\Http\Response;
use Nette\Http\Session;
use Nette\Http\UrlScript;
use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$czk = static fn(int $amount): Money => Money::of($amount, 'CZK');
$product = new Product(id: 6, name: 'Pánské tričko Classic', price: $czk(490));


// ---- Dispatcher: listeners run in order, only for their own event class.
$dispatcher = new EventDispatcher;
$calls = [];

$dispatcher->addListener(CartItemRemoved::class, function (CartItemRemoved $e) use (&$calls): void {
	$calls[] = 'first:' . $e->variantId;
});
$dispatcher->addListener(CartItemRemoved::class, function (CartItemRemoved $e) use (&$calls): void {
	$calls[] = 'second:' . $e->variantId;
});
$dispatcher->addListener(CartItemChanged::class, function () use (&$calls): void {
	$calls[] = 'changed';
});

$dispatcher->dispatch(new CartItemRemoved($product, 25, 'Barva: Bílá, Velikost: L'));
Assert::same(['first:25', 'second:25'], $calls);

// An event nobody listens to is simply ignored.
$dispatcher->dispatch(new ProductAddedToCart($product, $product->price));
Assert::same(['first:25', 'second:25'], $calls);

// An exception in a listener reaches the code that dispatched the event.
$dispatcher->addListener(ProductAddedToCart::class, static function (): void {
	throw new \RuntimeException('listener failed');
});
Assert::exception(
	fn() => $dispatcher->dispatch(new ProductAddedToCart($product, $product->price)),
	\RuntimeException::class,
	'listener failed',
);

// A listener that cannot be called is refused straight away, not skipped silently.
Assert::exception(
	fn() => $dispatcher->addListener(CartItemChanged::class, new \stdClass),
	\TypeError::class,
);


// ---- ProductAddedToCart carries the variant and lets a listener set the price.
$event = new ProductAddedToCart($product, $product->price);
Assert::null($event->variantId);
Assert::null($event->variantLabel);
Assert::same(1, $event->amount);

$event = new ProductAddedToCart($product, $product->price, 25, 'Barva: Bílá, Velikost: L', 3);
Assert::same(25, $event->variantId);
Assert::same('Barva: Bílá, Velikost: L', $event->variantLabel);
Assert::same(3, $event->amount);

$dispatcher = new EventDispatcher;
$dispatcher->addListener(ProductAddedToCart::class, static function (ProductAddedToCart $e) use ($czk): void {
	if ($e->variantId === 25) {
		$e->setPrice($czk(400));
	}
});
$dispatcher->dispatch($event);
Assert::true($event->getPrice()->isEqualTo($czk(400)));


// ---- OrderLoggerListener writes the ordered variants.
$session = new Session(new Request(new UrlScript('http://localhost/')), new Response);
$session->setExpiration('14 days');

$discountRepo = new class extends DiscountCodeRepository {
	public function __construct()
	{
	}


	public function findValid(string $code): ?DiscountCodeEntity
	{
		return null;
	}
};
$cart = new ShoppingCartSession(
	$session,
	new Commerce(['currency' => 'CZK', 'moneyFormat' => 'cs_CZ']),
	new DiscountCodeService($session, $discountRepo),
);
$cart->remove();

$plain = new ProductCart($product, BigInteger::of(1));
$variant = new ProductCart(
	new Product(id: 6, name: 'Pánské tričko Classic', price: $czk(490)),
	BigInteger::of(2),
	25,
	'Barva: Bílá, Velikost: L',
);
$variant->product->setDiscount(10);

$order = new OrderPlaced(
	orderId: 77,
	orderSummary: new OrderSummary(
		customer_id: 1,
		carrier_id: 1,
		payment_id: 1,
		carrier_price: 100,
		payment_price: 0,
		subtotal_price: 1000,
		total_price: 1100,
		discount_code: null,
		discount_amount: 0,
		created_at: new \DateTimeImmutable('2026-10-05 10:00:00'),
	),
	customer: new Customer('a@example.com', '+420123456789', 'Jan', 'Novák', 'Ulice 1', 'Praha', '11000', 'CZ'),
	carrier: new Carrier(1, 'PPL', $czk(100)),
	payment: new Payment(1, 'Dobírka', $czk(0)),
	shoppingCartSession: $cart,
	items: [$plain, $variant],
);

$log = (new OrderLoggerListener)->toArray($order);

Assert::same(77, $log['Order ID']);
Assert::same('Jan Novák', $log['Customer']['name']);
Assert::same('2026-10-05 10:00:00', $log['Created at']);
Assert::same([
	[
		'product_id' => 6,
		'product' => 'Pánské tričko Classic',
		'variant_id' => null,
		'variant' => null,
		'amount' => 1,
		'unit_price' => 490.0,
	],
	[
		'product_id' => 6,
		'product' => 'Pánské tričko Classic',
		'variant_id' => 25,
		'variant' => 'Barva: Bílá, Velikost: L',
		'amount' => 2,
		'unit_price' => 441.0,
	],
], $log['Items']);

// Without a snapshot, the log falls back to the cart session.
$cart->addItem($product, 1);
$legacy = new OrderPlaced(
	orderId: 78,
	orderSummary: $order->orderSummary,
	customer: $order->customer,
	carrier: $order->carrier,
	payment: $order->payment,
	shoppingCartSession: $cart,
);
Assert::count(1, (new OrderLoggerListener)->toArray($legacy)['Items']);
