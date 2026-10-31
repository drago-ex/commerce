<?php declare(strict_types=1);

namespace Drago\Commerce\Domain\Order;

use DateTimeImmutable;
use Drago\Database\Entity;


class OrderEntity extends Entity
{
	public const string Table = 'orders',
		PrimaryKey = 'id',
		ColumnStatus = 'status',
		ColumnPaidAt = 'paid_at',
		ColumnDiscountCode = 'discount_code';

	public int $id;
	public int $customerId;
	public int $carrierId;
	public string $carrierName;
	public int $paymentId;
	public string $paymentName;
	public float $carrierPrice;
	public float $paymentPrice;
	public float $subtotalPrice;
	public float $totalPrice;
	public ?string $discountCode;
	public float $discountAmount;
	public string $currency;
	public DateTimeImmutable $createdAt;
	public OrderStatus $status;
	public ?DateTimeImmutable $paidAt;
}
