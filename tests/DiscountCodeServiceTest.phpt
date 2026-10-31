<?php declare(strict_types=1);

namespace Drago\Commerce\Tests;

use Brick\Money\Money;
use Drago\Commerce\Domain\DiscountCode\DiscountCodeEntity;
use Drago\Commerce\Domain\DiscountCode\DiscountCodeRepository;
use Drago\Commerce\Domain\DiscountCode\DiscountType;
use Drago\Commerce\Service\DiscountCodeService;
use Nette\Http\Request;
use Nette\Http\Response;
use Nette\Http\Session;
use Nette\Http\UrlScript;
use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$repository = new class extends DiscountCodeRepository {
	/** @var array<string, DiscountCodeEntity> */
	public array $codes = [];

	/** @var list<int> */
	public array $consumedIds = [];

	public bool $usageResult = true;

	public int $lookups = 0;


	public function __construct()
	{
	}


	public function findValid(string $code): ?DiscountCodeEntity
	{
		$this->lookups++;

		return $this->codes[strtoupper(trim($code))] ?? null;
	}


	public function incrementUsage(int $id): bool
	{
		$this->consumedIds[] = $id;
		return $this->usageResult;
	}
};

$session = new Session(
	new Request(new UrlScript('http://localhost/')),
	new Response,
);
$session->setExpiration('14 days');
$service = new DiscountCodeService($session, $repository);

$percentCode = new DiscountCodeEntity;
$percentCode->id = 11;
$percentCode->code = 'SAVE15';
$percentCode->type = DiscountType::Percent;
$percentCode->value = 15;
$percentCode->validFrom = null;
$percentCode->validTo = null;
$percentCode->usageLimit = 10;
$percentCode->usedCount = 0;
$percentCode->minimumOrderAmount = 1000;
$percentCode->active = true;

$fixedCode = new DiscountCodeEntity;
$fixedCode->id = 12;
$fixedCode->code = 'FIXED500';
$fixedCode->type = DiscountType::Fixed;
$fixedCode->value = 500;
$fixedCode->validFrom = null;
$fixedCode->validTo = null;
$fixedCode->usageLimit = null;
$fixedCode->usedCount = 0;
$fixedCode->minimumOrderAmount = null;
$fixedCode->active = true;

$negativeCode = new DiscountCodeEntity;
$negativeCode->id = 13;
$negativeCode->code = 'NEGATIVE';
$negativeCode->type = DiscountType::Fixed;
$negativeCode->value = -100;
$negativeCode->validFrom = null;
$negativeCode->validTo = null;
$negativeCode->usageLimit = null;
$negativeCode->usedCount = 0;
$negativeCode->minimumOrderAmount = null;
$negativeCode->active = true;

$repository->codes = [
	$percentCode->code => $percentCode,
	$fixedCode->code => $fixedCode,
	$negativeCode->code => $negativeCode,
];

Assert::null($service->getCode());
Assert::true($service->consume());
Assert::false($service->apply('UNKNOWN'));
Assert::null($service->getCode());

Assert::true($service->apply(' save15 '));
Assert::same($percentCode, $service->getCode());
Assert::true($service->applyTo(Money::of(2000, 'CZK'))->isEqualTo(Money::of(1700, 'CZK')));
Assert::true($service->applyTo(Money::of(999, 'CZK'))->isEqualTo(Money::of(999, 'CZK')));
Assert::true($service->consume());
Assert::same([11], $repository->consumedIds);

Assert::true($service->apply('FIXED500'));
Assert::true($service->applyTo(Money::of(300, 'CZK'))->isEqualTo(Money::of(0, 'CZK')));

Assert::false($service->apply('NEGATIVE'));
Assert::true($service->applyTo(Money::of(1000, 'CZK'), $negativeCode)->isEqualTo(Money::of(1000, 'CZK')));

$service->remove();
$repository->usageResult = false;
Assert::true($service->apply('FIXED500'));
Assert::false($service->consume());
Assert::same([11, 12], $repository->consumedIds);

$service->remove();
Assert::null($service->getCode());

Assert::true($service->apply('FIXED500'));
$repository->codes = [];
Assert::null($service->getCode());
Assert::null($service->getCode());

// Consuming an explicit code records exactly that code, whatever is in the session.
$service->remove();
$repository->consumedIds = [];
$repository->usageResult = true;
Assert::true($service->consume($percentCode));
Assert::same([11], $repository->consumedIds);
$repository->usageResult = false;
Assert::false($service->consume($fixedCode));
Assert::same([11, 12], $repository->consumedIds);

// The code is looked up once per request, however many controls ask for it.
$service->remove();
$repository->codes = [$fixedCode->code => $fixedCode];
Assert::true($service->apply('FIXED500'));
$repository->lookups = 0;
Assert::same($fixedCode, $service->getCode());
Assert::same($fixedCode, $service->getCode());
Assert::true($service->applyTo(Money::of(100, 'CZK'))->isEqualTo(Money::of(0, 'CZK')));
Assert::same(1, $repository->lookups);

// Applying a code again forgets the remembered lookup.
Assert::true($service->apply('FIXED500'));
$repository->codes = [];
Assert::null($service->getCode());
