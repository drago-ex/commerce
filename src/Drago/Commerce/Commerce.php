<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Commerce;

use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;


class Commerce
{
	public static string $currency;
	public static string $moneyFormat;
	public static string $moneySymbol;
	public static int $moneyFractionDigits;

	/** @var array<string, mixed> */
	private array $config;


	/**
	 * @param array<string, mixed> $config
	 */
	public function __construct(array $config)
	{
		$this->config = $config;

		self::$currency = (string) ($config['currency'] ?? 'EUR');
		self::$moneyFormat = (string) ($config['moneyFormat'] ?? 'de_DE');
		self::$moneySymbol = (string) ($config['moneySymbol'] ?? '');
		self::$moneyFractionDigits = (int) ($config['moneyFractionDigits'] ?? 2);
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


	/**
	 * @return array<int|string, mixed>|string|false
	 */
	public function getDefaultRegionCode(): array|string|false
	{
		return $this->config['defaultRegionCode'] ?? false;
	}


	public function getPostCodeOnRegionPhone(): bool
	{
		return (bool) ($this->config['postCodeOnRegionPhone'] ?? false);
	}


	/**
	 * @return array<int|string, mixed>|string
	 */
	public function getAllowedRegionPhoneNumber(): array|string
	{
		return $this->config['allowedRegionPhoneNumber'] ?? [];
	}
}
