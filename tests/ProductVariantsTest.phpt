<?php

declare(strict_types=1);

namespace Drago\Commerce\Tests;

use Brick\Math\BigInteger;
use Brick\Money\Money;
use Drago\Commerce\Commerce;
use Drago\Commerce\Domain\DiscountCode\DiscountCodeRepository;
use Drago\Commerce\Domain\Product\Product;
use Drago\Commerce\Domain\Product\ProductCart;
use Drago\Commerce\Domain\Product\ProductVariantEntity;
use Drago\Commerce\Domain\Product\ProductVariantMapper;
use Drago\Commerce\Domain\Product\ProductVariantOption;
use Drago\Commerce\Domain\Product\ProductVariantRepository;
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

$discountRepo = (new class extends DiscountCodeRepository {
	public function __construct()
	{
	}
});

$discountService = new DiscountCodeService($session, $discountRepo);
$cartSession = new ShoppingCartSession($session, $commerce, $discountService);
$cartSession->remove();

// 1. Test ProductVariantOption DTO
$option = new ProductVariantOption(
	id: 10,
	sku: 'ROG-R9-5080',
	price: Money::of(54990, 'CZK'),
	stock: 3,
	labels: ['Procesor: AMD Ryzen 9', 'Grafika: RTX 5080'],
	attributeValueIds: [20, 23],
);

Assert::same(10, $option->id);
Assert::same('ROG-R9-5080', $option->sku);
Assert::true($option->inStock());
Assert::same('Procesor: AMD Ryzen 9, Grafika: RTX 5080', $option->getLabel());
Assert::same([20, 23], $option->attributeValueIds);

// 2. Test ProductVariantMapper
$dummyRepo = (new class extends ProductVariantRepository {
	public function __construct()
	{
	}


	public function getLabels(int $variantId): array
	{
		return ['Barva: Černá', 'Kapacita: 256 GB'];
	}


	public function getAttributeValueIds(int $variantId): array
	{
		return [1, 10];
	}


	public function getAttributesForProduct(int $productId): array
	{
		return [
			5 => [
				['attribute' => 'Barva', 'value' => 'Černá', 'valueId' => 1],
				['attribute' => 'Kapacita', 'value' => '256 GB', 'valueId' => 10],
			],
		];
	}
});

$mapper = new ProductVariantMapper($commerce, $dummyRepo);

$entityOverride = new ProductVariantEntity;
$entityOverride->id = 5;
$entityOverride->product_id = 2;
$entityOverride->sku = 'XYZ-256-BLK';
$entityOverride->price = 14500.0;
$entityOverride->stock = 4;
$entityOverride->active = 1;

$optionOverride = $mapper->map($entityOverride, 12500.0);
Assert::true($optionOverride->price?->isEqualTo(Money::of(14500, 'CZK')));
Assert::true($optionOverride->priceOverridden);
Assert::same([1, 10], $optionOverride->attributeValueIds);
Assert::same('Barva: Černá, Kapacita: 256 GB', $optionOverride->getLabel());

// Variant with price fallback (null price override)
$entityFallback = new ProductVariantEntity;
$entityFallback->id = 6;
$entityFallback->product_id = 2;
$entityFallback->sku = 'XYZ-128-BLK';
$entityFallback->price = null;
$entityFallback->stock = 6;
$entityFallback->active = 1;

$optionFallback = $mapper->map($entityFallback, 12500.0);
Assert::true($optionFallback->price?->isEqualTo(Money::of(12500, 'CZK')));
Assert::false($optionFallback->priceOverridden);

// Bulk mapping uses one attribute lookup for all variants of the product.
$options = $mapper->mapMany(2, [$entityOverride, $entityFallback], 12500.0);
Assert::count(2, $options);
Assert::same('Barva: Černá, Kapacita: 256 GB', $options[0]->getLabel());
Assert::same([1, 10], $options[0]->attributeValueIds);
Assert::true($options[0]->priceOverridden);
Assert::same([], $options[1]->labels);
Assert::same([], $options[1]->attributeValueIds);
Assert::true($options[1]->price?->isEqualTo(Money::of(12500, 'CZK')));
Assert::false($options[1]->priceOverridden);

// 3. Test ShoppingCartSession with multiple variants of the same product
$product = new Product(id: 2, name: 'Mobilní telefon XYZ', price: Money::of(12500, 'CZK'));

// Add 2x Variant 5 (Black 256GB - 14500 CZK)
$cartSession->addItem(
	product: new Product(id: 2, name: 'Mobilní telefon XYZ', price: Money::of(14500, 'CZK')),
	amount: 2,
	variantId: 5,
	variantLabel: 'Barva: Černá, Kapacita: 256 GB',
);

// Add 1x Variant 6 (Black 128GB - 12500 CZK)
$cartSession->addItem(
	product: new Product(id: 2, name: 'Mobilní telefon XYZ', price: Money::of(12500, 'CZK')),
	amount: 1,
	variantId: 6,
	variantLabel: 'Barva: Černá, Kapacita: 128 GB',
);

// Cart should contain 2 distinct lines for the same product
$items = $cartSession->getItems();
Assert::same(2, count($items));
Assert::same(3, $cartSession->getAmountItems());

// Subtotal: 2 * 14500 + 1 * 12500 = 41500 CZK
Assert::true($cartSession->getSubtotalPrice()->isEqualTo(Money::of(41500, 'CZK')));
Assert::true($cartSession->getTotalPrice()->isEqualTo(Money::of(41500, 'CZK')));
Assert::true($cartSession->getItems()[0]->getUnitPrice()->isEqualTo(Money::of(14500, 'CZK')));

$discountedProduct = new Product(id: 3, name: 'Tričko', price: Money::of(1000, 'CZK'));
$discountedProduct->setDiscount(10);
$discountedCartItem = new ProductCart($discountedProduct, BigInteger::one());
Assert::true($discountedCartItem->getUnitPrice()->isEqualTo(Money::of(900, 'CZK')));

// Update quantity of Variant 5
$cartSession->addItem(
	product: new Product(id: 2, name: 'Mobilní telefon XYZ', price: Money::of(14500, 'CZK')),
	amount: 1,
	variantId: 5,
);
Assert::same(4, $cartSession->getAmountItems()); // 3 * 14500 + 1 * 12500 = 56000 CZK
Assert::true($cartSession->getSubtotalPrice()->isEqualTo(Money::of(56000, 'CZK')));

// Remove Variant 5, Variant 6 remains
$cartSession->removeItem($product, variantId: 5);
Assert::same(1, count($cartSession->getItems()));
Assert::same(1, $cartSession->getAmountItems());
Assert::same(6, $cartSession->getItems()[0]->variantId);
Assert::true($cartSession->getSubtotalPrice()->isEqualTo(Money::of(12500, 'CZK')));

// Lines are found by product and variant together.
Assert::same(1, $cartSession->getAmount(2, 6));
Assert::same(0, $cartSession->getAmount(2, 5));
Assert::same(0, $cartSession->getAmount(2));
Assert::null($cartSession->findItem(2, 5));
Assert::same(6, $cartSession->findItem(2, 6)?->variantId);

// A non-variant product line stays separate from the same product's variant line.
$cartSession->addItem($product, 1);
Assert::same(2, count($cartSession->getItems()));
Assert::same(2, $cartSession->getAmountItems());

// Incrementing an existing variant changes only that variant line.
$cartSession->addItem(
	product: $product,
	amount: 2,
	variantId: 6,
);
Assert::same(2, count($cartSession->getItems()));
Assert::same(4, $cartSession->getAmountItems());
Assert::same(3, $cartSession->getItems()[0]->amount->toInt());
Assert::same(1, $cartSession->getItems()[1]->amount->toInt());

$cartSession->removeItem($product, variantId: 6);
Assert::same(1, count($cartSession->getItems()));
Assert::null($cartSession->getItems()[0]->variantId);

// A line can be removed by IDs alone (its product may no longer exist).
Assert::same(1, $cartSession->getAmount(2));
$cartSession->removeLine(2);
Assert::same(0, $cartSession->getAmount(2));
Assert::same([], $cartSession->getItems());

$cartSession->remove();
Assert::same([], $cartSession->getItems());
