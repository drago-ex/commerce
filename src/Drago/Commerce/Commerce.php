<?php

declare(strict_types=1);

namespace Drago\Commerce;

use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;


class Commerce
{
	public static string $currency;
	public static string $moneyFormat;

	private array $config;


	public function __construct(array $config)
	{
		$this->config = $config;

		self::$currency = (string) ($config['currency'] ?? 'EUR');
		self::$moneyFormat = (string) ($config['moneyFormat'] ?? 'de_DE');
	}


	public function moneyZero(): Money
	{
		return Money::zero(self::$currency);
	}


	/**
	 * @throws UnknownCurrencyException
	 */
	public function moneyOf(float $amount): Money
	{
		return Money::of($amount, self::$currency);
	}


	public function getDefaultRegionCode(): array|string|false
	{
		return $this->config['defaultRegionCode'] ?? false;
	}


	public function getPostCodeOnRegionPhone(): bool
	{
		return (bool) ($this->config['postCodeOnRegionPhone'] ?? false);
	}


	public function getAllowedRegionPhoneNumber(): array|string
	{
		return $this->config['allowedRegionPhoneNumber'] ?? [];
	}
}
