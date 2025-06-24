<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Commerce\Domain\Product;

use Brick\Math\RoundingMode;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Drago\Commerce\Commerce;
use Drago\Database\Entity;


class ProductEntity extends Entity
{
	public const string
		Table = 'products',
		PrimaryKey = 'id',
		Category = 'category',
		Name = 'name',
		Description = 'description',
		Discount = 'discount',
		Price = 'price',
		Photo = 'photo',
		Active = 'active';

	public int $id;
	public int $category;
	public string $name;
	public string $description;
	public ?int $discount;
	public float $price;
	public string $photo;
	public int $active;


	/**
	 * @throws UnknownCurrencyException
	 */
	public function getPrice(): Money
	{
		return Money::of($this->price, Commerce::$currency);
	}


	/**
	 * @throws UnknownCurrencyException
	 */
	public function getDiscountedPrice(): Money
	{
		$discountRatio = max(0, min(100, $this->discount ?? 0)) / 100;
		return $this->getPrice()->multipliedBy(1 - $discountRatio, RoundingMode::HALF_UP);
	}


	public function hasDiscount(): bool
	{
		return $this->discount !== null && $this->discount > 0;
	}


	public function getDiscountPercent(): int
	{
		return $this->discount ?? 0;
	}
}
