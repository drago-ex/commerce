<?php

declare(strict_types=1);

namespace Drago\Commerce\Tests;

use Drago\Commerce\Commerce;
use Drago\Commerce\Data\ReaderGeoLite;
use Tester\Assert;

require __DIR__ . '/bootstrap.php';

// Without a configured database every lookup degrades to null.
$unconfigured = new ReaderGeoLite(new Commerce([]));
Assert::null($unconfigured->getCity('203.0.113.7'));
Assert::null($unconfigured->getCountryIsoCode('203.0.113.7'));

// A database that cannot be opened is skipped instead of breaking the form.
$broken = new ReaderGeoLite(new Commerce(['geoLite2Path' => __DIR__ . '/missing.mmdb']));
Assert::null($broken->getCity('203.0.113.7'));
Assert::null($broken->getCountryIsoCode('203.0.113.7'));
