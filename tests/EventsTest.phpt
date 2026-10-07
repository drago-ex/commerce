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
use Drago\Commerce\Domain\Order\OrderSummary;
use Drago\Commerce\Domain\Product\Product;
use Drago\Commerce\Domain\Product\ProductCart;
use Drago\Commerce\Event\CartItemChanged;
use Drago\Commerce\Event\CartItemRemoved;
use Drago\Commerce\Event\EventDispatcher;
use Drago\Commerce\Event\OrderPlaced;
use Drago\Commerce\Event\ProductAddedToCart;
use Drago\Commerce\EventListener\OrderLoggerListener;
use Drago\Commerce\Mail\OrderConfirmationListener;
use Drago\Commerce\Mail\OrderConfirmationMailer;
use Drago\Commerce\Service\DiscountCodeService;
use Drago\Commerce\Service\ShoppingCartSession;
use Latte\Engine;
use Nette\Application\UI\Control;
use Nette\Bridges\ApplicationLatte\LatteFactory;
use Nette\Bridges\ApplicationLatte\TemplateFactory;
use Nette\Http\Request;
use Nette\Http\Response;
use Nette\Http\Session;
use Nette\Http\UrlScript;
use Nette\Localization\Translator;
use Nette\Mail\Mailer;
use Nette\Mail\Message;
use Tester\Assert;
use Tracy\Debugger;
use Tracy\ILogger;

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
		currency: 'CZK',
		carrier_name: 'PPL',
		payment_name: 'Dobírka',
	),
	customer: new Customer(
		'a@example.com',
		'+420123456789',
		'Jan',
		'Novák',
		'Ulice 1',
		'Praha',
		'11000',
		'CZ',
		note: "Zavolat předem.\nNechat u sousedů.",
	),
	carrier: new Carrier(1, 'PPL', $czk(100)),
	payment: new Payment(1, 'Dobírka', $czk(0)),
	shoppingCartSession: $cart,
	items: [$plain, $variant],
	lang: 'cs',
);

$log = (new OrderLoggerListener)->toArray($order);

Assert::same(77, $log['Order ID']);
Assert::same(1, $log['Customer ID']);
Assert::false(isset($log['Customer']));
Assert::notContains('Novák', json_encode($log, JSON_UNESCAPED_UNICODE));
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

$mailer = new class implements Mailer {
	/** @var list<Message> */
	public array $messages = [];


	public function send(Message $mail): void
	{
		$this->messages[] = $mail;
	}
};
$templateFactory = new TemplateFactory(new class implements LatteFactory {
	public function create(?Control $control = null): Engine
	{
		return new Engine;
	}
});
$translator = new class implements Translator {
	public string $lang = 'en';


	public function setTranslate(string $lang): void
	{
		$this->lang = $lang;
	}


	public function translate(string|\Stringable $message, mixed ...$parameters): string
	{
		$translated = $this->lang === 'cs' ? match ((string) $message) {
			'Order confirmation #%d' => 'Potvrzení objednávky č. %d',
			'Thank you for your order' => 'Děkujeme za objednávku',
			default => (string) $message,
		} : (string) $message;
		return $parameters === [] ? $translated : sprintf($translated, ...$parameters);
	}
};
$confirmationMailer = new OrderConfirmationMailer(
	$mailer,
	$templateFactory,
	'orders@example.cz',
	translator: $translator,
	storeName: 'Test Shop',
	storeEmail: 'support@example.cz',
);
(new OrderConfirmationListener($confirmationMailer))($order);

Assert::same('cs', $translator->lang);
Assert::count(1, $mailer->messages);
Assert::same('Potvrzení objednávky č. 77', $mailer->messages[0]->getSubject());
Assert::contains('a@example.com', (string) json_encode($mailer->messages[0]->getHeader('To')));
Assert::contains('<html lang="cs">', $mailer->messages[0]->getHtmlBody());
Assert::contains('#77', $mailer->messages[0]->getHtmlBody());
Assert::contains('Děkujeme za objednávku', $mailer->messages[0]->getHtmlBody());
Assert::contains('Pánské tričko Classic', $mailer->messages[0]->getHtmlBody());
Assert::contains('882,00', $mailer->messages[0]->getHtmlBody());
Assert::contains('1 100,00', $mailer->messages[0]->getHtmlBody());
Assert::contains('PPL', $mailer->messages[0]->getHtmlBody());
Assert::contains('Test Shop', $mailer->messages[0]->getHtmlBody());
$emailBody = substr($mailer->messages[0]->getHtmlBody(), strpos($mailer->messages[0]->getHtmlBody(), '<body'));
Assert::true(strpos($emailBody, 'Test Shop') < strpos($emailBody, 'Order confirmation'));
Assert::contains('Zavolat předem.<br', $mailer->messages[0]->getHtmlBody());

$fallbackMailer = new OrderConfirmationMailer($mailer, $templateFactory, 'orders@example.cz');
Debugger::setLogger(new class implements ILogger {
	/** @var list<mixed> */
	public array $entries = [];


	public function log(mixed $value, string $level = self::INFO): void
	{
		$this->entries[] = $value;
	}
});
(new OrderConfirmationListener($fallbackMailer))($order);
Assert::same([], Debugger::getLogger()->entries);
Assert::same('Order confirmation #77', $mailer->messages[1]->getSubject());

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
