<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Commerce\Domain\Product;

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
}
