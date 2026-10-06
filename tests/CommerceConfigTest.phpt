<?php

declare(strict_types=1);

namespace Drago\Commerce\Tests;

use Drago\Commerce\Commerce;
use Tester\Assert;

require __DIR__ . '/bootstrap.php';

Assert::same(12, new Commerce([])->getItemsPerPage());
Assert::same(24, new Commerce(['itemsPerPage' => 24])->getItemsPerPage());

// Zero turns paging off; a negative value is treated the same way.
Assert::same(0, new Commerce(['itemsPerPage' => 0])->getItemsPerPage());
Assert::same(0, new Commerce(['itemsPerPage' => -5])->getItemsPerPage());
