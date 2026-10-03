<?php

declare(strict_types=1);

namespace Drago\Commerce\Tests;

use Brick\Money\Money;
use Drago\Commerce\Commerce;
use Drago\Commerce\Domain\Customer\Customer;
use Drago\Commerce\Domain\Delivery\Carrier;
use Drago\Commerce\Domain\Delivery\Payment;
use Drago\Commerce\Service\OrderSession;
use Nette\Http\Request;
use Nette\Http\Response;
use Nette\Http\Session;
use Nette\Http\UrlScript;
use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$session = new Session(
	new Request(new UrlScript('http://localhost/')),
	new Response,
);
$commerce = new Commerce([
	'currency' => 'CZK',
	'moneyFormat' => 'cs_CZ',
]);
$orderSession = new OrderSession($session, $commerce);

$emptyOrder = $orderSession->getItems();
Assert::null($emptyOrder->carrier);
Assert::null($emptyOrder->payment);
Assert::null($emptyOrder->customer);
Assert::true($orderSession->getCarrierPrice()->isEqualTo(Money::of(0, 'CZK')));
Assert::true($orderSession->getPaymentPrice()->isEqualTo(Money::of(0, 'CZK')));

$carrier = new Carrier(1, 'Courier', Money::of(120, 'CZK'));
$payment = new Payment(2, 'Card', Money::of(15, 'CZK'));
$customer = new Customer(
	email: 'buyer@example.com',
	phone: '+420123456789',
	name: 'Jan',
	surname: 'Novak',
	street: 'Main Street 1',
	city: 'Prague',
	postal_code: '11000',
	country: 'CZ',
	note: 'Call on arrival',
);

$orderSession->setCarrier($carrier);
$orderSession->setPayment($payment);
$orderSession->setCustomer($customer);

$order = $orderSession->getItems();
Assert::same($carrier, $order->carrier);
Assert::same($payment, $order->payment);
Assert::same($customer, $order->customer);
Assert::true($orderSession->getCarrierPrice()->isEqualTo(Money::of(120, 'CZK')));
Assert::true($orderSession->getPaymentPrice()->isEqualTo(Money::of(15, 'CZK')));

$orderSession->remove();
$clearedOrder = $orderSession->getItems();
Assert::null($clearedOrder->carrier);
Assert::null($clearedOrder->payment);
Assert::null($clearedOrder->customer);
Assert::true($orderSession->getCarrierPrice()->isEqualTo(Money::of(0, 'CZK')));
Assert::true($orderSession->getPaymentPrice()->isEqualTo(Money::of(0, 'CZK')));
