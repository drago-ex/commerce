<?php

declare(strict_types=1);

namespace App\Commerce\Domain;


/**
 * Adds discount property and related methods to a class.
 * Helps to reuse discount logic across multiple domain models.
 */
trait Discount
{
	public ?int $discount = null;


	public function setDiscount(?int $discount): void
	{
		$this->discount = $discount;
	}


	public function getDiscount(): ?int
	{
		return $this->discount;
	}
}
