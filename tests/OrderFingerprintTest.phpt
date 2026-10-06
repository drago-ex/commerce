<?php

declare(strict_types=1);

namespace Drago\Commerce\Tests;

use Brick\Math\BigInteger;
use Brick\Money\Money;
use Drago\Commerce\Domain\Delivery\Carrier;
use Drago\Commerce\Domain\Order\OrderFingerprint;
use Drago\Commerce\Domain\Order\OrderState;
use Drago\Commerce\Domain\Product\Product;
use Drago\Commerce\Domain\Product\ProductCart;
use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$total = Money::of(100, 'CZK');
$emptyDiscount = Money::zero('CZK');
$order = new OrderState(null, null, null);
$firstItem = new ProductCart(new Product(1, 'Item', $total), BigInteger::one());
$secondItem = new ProductCart(new Product(2, 'Item', $total), BigInteger::one());

$fingerprint = OrderFingerprint::create(
	[$firstItem],
	$order,
	null,
	$total,
	$emptyDiscount,
	$total,
);

Assert::same($fingerprint, OrderFingerprint::create(
	[$firstItem],
	$order,
	null,
	$total,
	$emptyDiscount,
	$total,
));

Assert::same(
	OrderFingerprint::create(
		[$firstItem, $secondItem],
		$order,
		null,
		Money::of(200, 'CZK'),
		$emptyDiscount,
		Money::of(200, 'CZK'),
	),
	OrderFingerprint::create(
		[$secondItem, $firstItem],
		$order,
		null,
		Money::of(200, 'CZK'),
		$emptyDiscount,
		Money::of(200, 'CZK'),
	),
);

// A different item must be detected even if the displayed total is unchanged.
Assert::notSame($fingerprint, OrderFingerprint::create(
	[$secondItem],
	$order,
	null,
	$total,
	$emptyDiscount,
	$total,
));

$firstItem->amount = BigInteger::of(2);
Assert::notSame($fingerprint, OrderFingerprint::create(
	[$firstItem],
	$order,
	null,
	$total,
	$emptyDiscount,
	$total,
));

// Delivery choice identity is part of the reviewed order, not only its price.
$order->carrier = new Carrier(1, 'Courier', Money::of(0, 'CZK'));
$withCarrier = OrderFingerprint::create(
	[$firstItem],
	$order,
	null,
	$total,
	$emptyDiscount,
	$total,
);
$order->carrier = new Carrier(2, 'Pickup', Money::of(0, 'CZK'));
Assert::notSame($withCarrier, OrderFingerprint::create(
	[$firstItem],
	$order,
	null,
	$total,
	$emptyDiscount,
	$total,
));
