<?php

declare(strict_types=1);

namespace Drago\Commerce\Domain\DiscountCode;

use DateTimeImmutable;
use Drago\Database\Entity;


class DiscountCodeEntity extends Entity
{
	public const string
		Table = 'discount_codes',
		PrimaryKey = 'id';

	public int $id;
	public string $code;
	public string $type;
	public float $value;
	public ?DateTimeImmutable $valid_from;
	public ?DateTimeImmutable $valid_to;
	public ?int $usage_limit;
	public int $used_count;
	public ?float $minimum_order_amount;
	public int $active;
}
