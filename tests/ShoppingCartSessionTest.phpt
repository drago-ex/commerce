<?php

declare(strict_types=1);

namespace Drago\Commerce\Tests;

use Brick\Money\Money;
use Drago\Commerce\Commerce;
use Drago\Commerce\Domain\Product\Product;
use Drago\Commerce\Domain\DiscountCode\DiscountCodeEntity;
use Drago\Commerce\Domain\DiscountCode\DiscountCodeRepository;
use Drago\Commerce\Service\DiscountCodeService;
use Drago\Commerce\Service\ShoppingCartSession;
use Nette\Http\Request;
use Nette\Http\Response;
use Nette\Http\Session;
use Nette\Http\UrlScript;
use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$httpRequest = new Request(new UrlScript('http://localhost/'));
$httpResponse = new Response;
$session = new Session($httpRequest, $httpResponse);
$session->setExpiration('14 days');

$commerce = new Commerce([
	'currency' => 'CZK',
	'moneyFormat' => 'cs_CZ',
]);

// Dummy repository to test discount codes
$discountRepo = (new class extends DiscountCodeRepository {
	/** @var array<string, DiscountCodeEntity> */
	public array $codes = [];

	public function __construct() {}

	public function findValid(string $code): ?DiscountCodeEntity {
		return $this->codes[strtoupper($code)] ?? null;
	}
});

$discountService = new DiscountCodeService($session, $discountRepo);
$cartSession = new ShoppingCartSession($session, $commerce, $discountService);

// Clear any existing session items
$cartSession->remove();
$discountService->remove();

Assert::same([], $cartSession->getItems());
Assert::same(0, $cartSession->getAmountItems());
Assert::true($cartSession->getSubtotalPrice()->isEqualTo(Money::of(0, 'CZK')));
Assert::true($cartSession->getTotalPrice()->isEqualTo(Money::of(0, 'CZK')));

// Add product without discount
$p1 = new Product(1, 'Product 1', Money::of(100, 'CZK'));
$cartSession->addItem($p1, 2);

Assert::same(1, count($cartSession->getItems()));
Assert::same(2, $cartSession->getAmountItems());
Assert::true($cartSession->getOriginalPrice()->isEqualTo(Money::of(200, 'CZK')));
Assert::true($cartSession->getSubtotalPrice()->isEqualTo(Money::of(200, 'CZK')));
Assert::true($cartSession->getTotalPrice()->isEqualTo(Money::of(200, 'CZK')));

// Add product with 10% discount
$p2 = new Product(2, 'Product 2', Money::of(200, 'CZK'));
$p2->setDiscount(10); // 180 CZK each
$cartSession->addItem($p2, 1);

Assert::same(2, count($cartSession->getItems()));
Assert::same(3, $cartSession->getAmountItems());
Assert::true($cartSession->getOriginalPrice()->isEqualTo(Money::of(400, 'CZK')));
Assert::true($cartSession->getSubtotalPrice()->isEqualTo(Money::of(380, 'CZK')));
Assert::true($cartSession->getTotalPrice()->isEqualTo(Money::of(380, 'CZK')));

// Update quantity with dontCount=true
$cartSession->addItem($p1, 5, dontCount: true);
Assert::same(6, $cartSession->getAmountItems()); // 5 * 100 + 1 * 180 = 680 CZK
Assert::true($cartSession->getSubtotalPrice()->isEqualTo(Money::of(680, 'CZK')));

// Apply fixed discount code (e.g. 80 CZK off)
$discountEntity = new DiscountCodeEntity;
$discountEntity->id = 1;
$discountEntity->code = 'DISCOUNT80';
$discountEntity->type = 'fixed';
$discountEntity->value = 80.0;
$discountEntity->minimum_order_amount = null;
$discountRepo->codes['DISCOUNT80'] = $discountEntity;

Assert::true($discountService->apply('DISCOUNT80'));
Assert::same('DISCOUNT80', $discountService->getCode()?->code);
Assert::true($cartSession->getTotalPrice()->isEqualTo(Money::of(600, 'CZK')));

// Remove discount code
$discountService->remove();
Assert::null($discountService->getCode());
Assert::true($cartSession->getTotalPrice()->isEqualTo(Money::of(680, 'CZK')));

// Remove item
$cartSession->removeItem($p2);
Assert::same(1, count($cartSession->getItems()));
Assert::same(5, $cartSession->getAmountItems());
Assert::true($cartSession->getSubtotalPrice()->isEqualTo(Money::of(500, 'CZK')));

// Empty cart
$cartSession->remove();
Assert::same([], $cartSession->getItems());
Assert::same(0, $cartSession->getAmountItems());
