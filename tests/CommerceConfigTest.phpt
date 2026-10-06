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
