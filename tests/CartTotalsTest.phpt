<?php

declare(strict_types=1);

namespace Drago\Commerce\Tests;

use Brick\Money\Money;
use Drago\Commerce\Commerce;
use Drago\Commerce\Domain\DiscountCode\DiscountCodeEntity;
use Drago\Commerce\Domain\DiscountCode\DiscountCodeRepository;
use Drago\Commerce\Domain\Product\Product;
use Drago\Commerce\Service\DiscountCodeService;
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

$code = new DiscountCodeEntity;
$code->id = 1;
$code->code = 'SAVE200';
$code->type = 'fixed';
$code->value = 200;
$code->valid_from = null;
$code->valid_to = null;
$code->usage_limit = null;
$code->used_count = 0;
$code->minimum_order_amount = null;
$code->active = 1;

$repository = new class ($code) extends DiscountCodeRepository {
	public function __construct(
		private readonly DiscountCodeEntity $code,
	) {
	}


	public function findValid(string $code): ?DiscountCodeEntity
	{
		return strtoupper($code) === $this->code->code ? $this->code : null;
	}
};

$session = new Session(new Request(new UrlScript('http://localhost/')), new Response);
$session->setExpiration('14 days');
$discountService = new DiscountCodeService($session, $repository);
$cart = new ShoppingCartSession($session, $commerce, $discountService);

// An empty cart has all prices at zero.
$empty = $cart->getTotals();
Assert::same([], $empty->items);
Assert::same(0, $empty->amountItems);
Assert::true($empty->cartTotalPrice->isZero());
Assert::true($empty->discountAmount->isZero());
Assert::null($empty->discountCode);

// 2 x 500 with a 10 % product discount.
$product = new Product(id: 1, name: 'Item', price: $czk(500));
$product->setDiscount(10);
$cart->addItem($product, 2);

$totals = $cart->getTotals();
Assert::count(1, $totals->items);
Assert::same(2, $totals->amountItems);
Assert::true($totals->originalPrice->isEqualTo($czk(1000)));
Assert::true($totals->subtotalPrice->isEqualTo($czk(900)));
Assert::true($totals->productDiscountAmount->isEqualTo($czk(100)));
Assert::true($totals->discountAmount->isZero());
Assert::true($totals->cartTotalPrice->isEqualTo($czk(900)));
Assert::null($totals->discountCode);

// Delivery and payment are added on top.
Assert::true($totals->withExtras($czk(150), $czk(0))->isEqualTo($czk(1050)));
Assert::true($totals->withExtras()->isEqualTo($czk(900)));

// A discount code lowers the cart total and is reported with the totals.
Assert::true($discountService->apply('save200'));
$discounted = $cart->getTotals();
Assert::same($code, $discounted->discountCode);
Assert::true($discounted->discountAmount->isEqualTo($czk(200)));
Assert::true($discounted->cartTotalPrice->isEqualTo($czk(700)));
Assert::true($discounted->withExtras($czk(150))->isEqualTo($czk(850)));

$discountService->remove();
$cart->remove();
