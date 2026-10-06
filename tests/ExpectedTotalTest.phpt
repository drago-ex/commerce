<?php

declare(strict_types=1);

namespace Drago\Commerce\Tests;

use Brick\Money\Money;
use Drago\Commerce\Domain\Order\ExpectedTotal;
use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$total = Money::of('1234.50', 'CZK');

Assert::same('1234.50', ExpectedTotal::format($total));
Assert::true(ExpectedTotal::matches(ExpectedTotal::format($total), $total));

// The comparison is numeric, not textual.
Assert::true(ExpectedTotal::matches('1234.5', $total));
Assert::true(ExpectedTotal::matches('1234.500', $total));

// A different total, or a value that is not a number, does not match.
Assert::false(ExpectedTotal::matches('1100.00', $total));
Assert::false(ExpectedTotal::matches('abc', $total));

// Without the field (custom template) the check is skipped.
Assert::true(ExpectedTotal::matches(null, $total));
Assert::true(ExpectedTotal::matches('', $total));
