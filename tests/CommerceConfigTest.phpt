<?php

declare(strict_types=1);

namespace Drago\Commerce\Tests;

use Drago\Commerce\Commerce;
use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$default = new Commerce([]);
Assert::same(12, $default->getItemsPerPage());

$custom = new Commerce(['itemsPerPage' => 24]);
Assert::same(24, $custom->getItemsPerPage());

// Zero turns paging off; a negative value is treated the same way.
$disabled = new Commerce(['itemsPerPage' => 0]);
Assert::same(0, $disabled->getItemsPerPage());

$negative = new Commerce(['itemsPerPage' => -5]);
Assert::same(0, $negative->getItemsPerPage());

// Prices from the database are floats; their decimal string must not depend on the precision setting of PHP,
// otherwise Money::of() gets e.g. "19.989999999999998" and throws.
$precision = ini_get('precision');
foreach (['14', '17', '-1'] as $setting) {
	ini_set('precision', $setting);
	Assert::same(['19.99', '0.93', '1234567.89', '0', '5', '-12.5'], array_map(
		Commerce::decimal(...),
		[19.99, 1 - 0.07, 1234567.89, 0.0, 5, -12.5],
	));
}

ini_set('precision', (string) $precision);
