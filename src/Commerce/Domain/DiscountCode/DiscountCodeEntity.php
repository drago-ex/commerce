<?php declare(strict_types=1);

namespace Drago\Commerce\Domain\DiscountCode;

use DateTimeImmutable;
use Drago\Database\Entity;


class DiscountCodeEntity extends Entity
{
	public const string Table = 'discount_codes',
		PrimaryKey = 'id';

	public int $id;
	public string $code;
	public string $type;
	public float $value;
	public ?DateTimeImmutable $validFrom;
	public ?DateTimeImmutable $validTo;
	public ?int $usageLimit;
	public int $usedCount;
	public ?float $minimumOrderAmount;
	public int $active;
}
