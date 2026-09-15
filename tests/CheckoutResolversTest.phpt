<?php

declare(strict_types=1);

namespace Drago\Commerce\Tests;

use Brick\Money\Money;
use Drago\Commerce\Commerce;
use Drago\Commerce\Domain\Checkout\CheckoutRedirectResolver;
use Drago\Commerce\Domain\Checkout\CheckoutStepResolver;
use Drago\Commerce\Domain\Checkout\CheckoutSteps;
use Drago\Commerce\Domain\Customer\Customer;
use Drago\Commerce\Domain\Delivery\Carrier;
use Drago\Commerce\Domain\Delivery\Payment;
use Drago\Commerce\Domain\DiscountCode\DiscountCodeEntity;
use Drago\Commerce\Domain\DiscountCode\DiscountCodeRepository;
use Drago\Commerce\Domain\Product\Product;
use Drago\Commerce\Service\DiscountCodeService;
use Drago\Commerce\Service\OrderSession;
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

// Create mock/dummy repository for DiscountCodeService
$discountRepo = (new class extends DiscountCodeRepository {
	public function __construct()
	{
	}


	public function findValid(string $code): ?DiscountCodeEntity
	{
		return null;
	}
});

$discountService = new DiscountCodeService($session, $discountRepo);
$cartSession = new ShoppingCartSession($session, $commerce, $discountService);
$orderSession = new OrderSession($session, $commerce);
$steps = new CheckoutSteps;

$redirectResolver = new CheckoutRedirectResolver($cartSession, $orderSession, $steps);
$stepResolver = new CheckoutStepResolver($cartSession, $orderSession, $steps);

// 1. Initial empty state
Assert::same([], $stepResolver->getCompletedSteps());
Assert::same('default', $redirectResolver->getRedirectTargetForAction('delivery'));
Assert::same('default', $redirectResolver->getRedirectTargetForAction('customer'));
Assert::same('default', $redirectResolver->getRedirectTargetForAction('summary'));

// 2. Add product to cart
$product = new Product(1, 'Item 1', Money::of(500, 'CZK'));
$cartSession->addItem($product, 2);

Assert::same(2, $cartSession->getAmountItems());
Assert::same(['shoppingCart'], $stepResolver->getCompletedSteps());
Assert::null($redirectResolver->getRedirectTargetForAction('delivery'));
Assert::same('delivery', $redirectResolver->getRedirectTargetForAction('customer'));
Assert::same('delivery', $redirectResolver->getRedirectTargetForAction('summary'));

// 3. Set carrier & payment
$carrier = new Carrier(1, 'DPD', Money::of(100, 'CZK'));
$payment = new Payment(1, 'Online', Money::of(0, 'CZK'));
$orderSession->setCarrier($carrier);
$orderSession->setPayment($payment);

Assert::same(['shoppingCart', 'delivery'], $stepResolver->getCompletedSteps());
Assert::null($redirectResolver->getRedirectTargetForAction('delivery'));
Assert::null($redirectResolver->getRedirectTargetForAction('customer'));
Assert::same('customer', $redirectResolver->getRedirectTargetForAction('summary'));

// 4. Set customer
$customer = new Customer(
	email: 'test@example.com',
	phone: '+420123456789',
	name: 'Jan',
	surname: 'Novak',
	street: 'Hlavni 1',
	city: 'Praha',
	postal_code: '11000',
	country: 'CZ',
);
$orderSession->setCustomer($customer);

Assert::same(['shoppingCart', 'delivery', 'customer', 'summary'], $stepResolver->getCompletedSteps());
Assert::null($redirectResolver->getRedirectTargetForAction('delivery'));
Assert::null($redirectResolver->getRedirectTargetForAction('customer'));
Assert::null($redirectResolver->getRedirectTargetForAction('summary'));

// Clean up session
$cartSession->remove();
$orderSession->remove();
