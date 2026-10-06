<?php

declare(strict_types=1);

namespace Drago\Commerce\Tests;

use Brick\Money\Money;
use Drago\Commerce\Commerce;
use Drago\Commerce\Domain\Delivery\Carrier;
use Drago\Commerce\Domain\Delivery\CarrierEntity;
use Drago\Commerce\Domain\Delivery\CarrierMapper;
use Drago\Commerce\Domain\Delivery\CarrierRepository;
use Drago\Commerce\Domain\Delivery\Payment;
use Drago\Commerce\Domain\Delivery\PaymentEntity;
use Drago\Commerce\Domain\Delivery\PaymentMapper;
use Drago\Commerce\Domain\Delivery\PaymentRepository;
use Drago\Commerce\Domain\DiscountCode\DiscountCodeEntity;
use Drago\Commerce\Domain\DiscountCode\DiscountCodeRepository;
use Drago\Commerce\Domain\Product\PriceResolver;
use Drago\Commerce\Domain\Product\ProductEntity;
use Drago\Commerce\Domain\Product\ProductRepository;
use Drago\Commerce\Domain\Product\ProductVariantRepository;
use Drago\Commerce\Event\EventDispatcher;
use Drago\Commerce\Event\ProductAddedToCart;
use Drago\Commerce\Service\CheckoutPricing;
use Drago\Commerce\Service\DiscountCodeService;
use Drago\Commerce\Service\OrderSession;
use Drago\Commerce\Service\ShoppingCartSession;
use Nette\Http\Request;
use Nette\Http\Response;
use Nette\Http\Session;
use Nette\Http\UrlScript;
use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$czk = static fn(int $amount): Money => Money::of($amount, 'CZK');

$commerce = new Commerce([
	'currency' => 'CZK',
	'moneyFormat' => 'cs_CZ',
]);

$session = new Session(new Request(new UrlScript('http://localhost/')), new Response);
$session->setExpiration('14 days');

$discountService = new DiscountCodeService($session, new class extends DiscountCodeRepository {
	public function __construct()
	{
	}


	public function findValid(string $code): ?DiscountCodeEntity
	{
		return null;
	}
});

$products = new class extends ProductRepository {
	/** @var array<int, ProductEntity> */
	public array $entities = [];


	public function __construct()
	{
	}


	public function getOne(int $id): ?ProductEntity
	{
		return $this->entities[$id] ?? null;
	}
};

$variants = new class extends ProductVariantRepository {
	public function __construct()
	{
	}
};

$carriers = new class extends CarrierRepository {
	/** @var array<int, CarrierEntity> */
	public array $entities = [];


	public function __construct()
	{
	}


	public function getOne(int $id): ?CarrierEntity
	{
		return $this->entities[$id] ?? null;
	}
};

$payments = new class extends PaymentRepository {
	/** @var array<int, PaymentEntity> */
	public array $entities = [];


	public function __construct()
	{
	}


	public function getOne(int $id): ?PaymentEntity
	{
		return $this->entities[$id] ?? null;
	}
};

$entity = new ProductEntity;
$entity->id = 1;
$entity->category = 1;
$entity->name = 'Item';
$entity->description = '';
$entity->discount = null;
$entity->price = 500.0;
$entity->photo = '';
$entity->active = 1;
$entity->stock = 10;
$products->entities[1] = $entity;

$dispatcher = new EventDispatcher;
$resolver = new PriceResolver($commerce, $dispatcher);
$cart = new ShoppingCartSession($session, $commerce, $discountService);
$order = new OrderSession($session, $commerce);

$pricing = new CheckoutPricing(
	$cart,
	$order,
	$products,
	$variants,
	$resolver,
	$carriers,
	$payments,
	new CarrierMapper($commerce),
	new PaymentMapper($commerce),
);

$unitPrice = static fn(): Money => $cart->getItems()[0]->getUnitPrice();


// ---- Resolver: catalog price and discount of a plain product.
$catalog = $resolver->catalog($entity);
Assert::true($catalog->price->isEqualTo($czk(500)));
Assert::null($catalog->discount);

// ---- A line added at the current catalog price is up to date.
$cart->addItem($resolver->forCart($entity, null, null, 2), 2);
Assert::same(2, $cart->getAmountItems());
Assert::same([], $pricing->refreshCart());

// ---- The catalog price changes: the line is repriced, the amount is kept.
$entity->price = 600.0;
Assert::same(['Item'], $pricing->refreshCart());
Assert::true($unitPrice()->isEqualTo($czk(600)));
Assert::same(2, $cart->getAmountItems());
Assert::same([], $pricing->refreshCart());

// ---- A new percentage discount is picked up as well.
$entity->discount = 10;
Assert::same(['Item'], $pricing->refreshCart());
Assert::true($unitPrice()->isEqualTo($czk(540)));

// ---- Adding the product again refreshes the price of the existing line.
$entity->discount = null;
$cart->addItem($resolver->forCart($entity, null, null, 1), 1);
Assert::true($unitPrice()->isEqualTo($czk(600)));
Assert::same(3, $cart->getAmountItems());

// ---- An inactive product is left to the stock reservation.
$entity->active = 0;
$entity->price = 700.0;
Assert::same([], $pricing->refreshCart());
Assert::true($unitPrice()->isEqualTo($czk(600)));
$entity->active = 1;
$cart->remove();

// ---- A price set by a ProductAddedToCart listener survives while the catalog stays the same.
$dispatcher->addListener(ProductAddedToCart::class, static function (ProductAddedToCart $event) use ($czk): void {
	$event->setPrice($czk(400));
});
$entity->price = 500.0;
$cart->addItem($resolver->forCart($entity, null, null, 1));
Assert::true($unitPrice()->isEqualTo($czk(400)));
Assert::same([], $pricing->refreshCart());
Assert::true($unitPrice()->isEqualTo($czk(400)));
$cart->remove();


// ---- Carrier and payment: price change updates the selection, a removed option is dropped.
$carrierEntity = new CarrierEntity;
$carrierEntity->id = 1;
$carrierEntity->name = 'DPD';
$carrierEntity->price = 100.0;
$carriers->entities[1] = $carrierEntity;

$paymentEntity = new PaymentEntity;
$paymentEntity->id = 1;
$paymentEntity->name = 'Online';
$paymentEntity->price = 0.0;
$payments->entities[1] = $paymentEntity;

$order->setCarrier(new Carrier(1, 'DPD', $czk(100)));
$order->setPayment(new Payment(1, 'Online', $czk(0)));
Assert::false($pricing->refreshDelivery());

$carrierEntity->price = 120.0;
Assert::true($pricing->refreshDelivery());
Assert::true($order->getCarrierPrice()->isEqualTo($czk(120)));
Assert::false($pricing->refreshDelivery());

unset($carriers->entities[1], $payments->entities[1]);
Assert::true($pricing->refreshDelivery());
Assert::null($order->getItems()->carrier);
Assert::null($order->getItems()->payment);

$order->remove();
