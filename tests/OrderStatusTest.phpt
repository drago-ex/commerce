<?php declare(strict_types=1);

use Drago\Commerce\Domain\Order\OrderStatus;
use Tester\Assert;

require __DIR__ . '/bootstrap.php';

// a new order is processed, shipped and completed, and can be cancelled until it is shipped
Assert::same([OrderStatus::Processing, OrderStatus::Cancelled], OrderStatus::New->next());
Assert::same([OrderStatus::Shipped, OrderStatus::Cancelled], OrderStatus::Processing->next());
Assert::same([OrderStatus::Completed], OrderStatus::Shipped->next());
Assert::same([], OrderStatus::Completed->next());
Assert::same([], OrderStatus::Cancelled->next());

Assert::true(OrderStatus::New->canChangeTo(OrderStatus::Cancelled));
Assert::false(OrderStatus::Shipped->canChangeTo(OrderStatus::Cancelled));
Assert::false(OrderStatus::Cancelled->canChangeTo(OrderStatus::New));
Assert::false(OrderStatus::New->canChangeTo(OrderStatus::New));
