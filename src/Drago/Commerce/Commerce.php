<?php

declare(strict_types=1);

namespace Drago\Commerce;

use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;


/**
 * Central configuration class for the Commerce module.
 * Holds global currency and money formatting settings used throughout the package.
 */
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


	/**
	 * Returns a zero-value Money object in the configured currency.
	 */
	public function moneyZero(): Money
	{
		return Money::zero(self::$currency);
	}


	/**
	 * Creates a Money object from a float amount in the configured currency.
	 *
	 * @throws UnknownCurrencyException
	 */
	public function moneyOf(float $amount): Money
	{
		return Money::of($amount, self::$currency);
	}


	/**
	 * Returns the default region code for phone number formatting or false when unset.
	 *
	 * @return array<int|string, mixed>|string|false
	 */
	public function getDefaultRegionCode(): array|string|false
	{
		return $this->config['defaultRegionCode'] ?? false;
	}


	/**
	 * Returns whether the postal code should be validated against the phone region.
	 */
	public function getPostCodeOnRegionPhone(): bool
	{
		return (bool) ($this->config['postCodeOnRegionPhone'] ?? false);
	}


	/**
	 * Returns allowed region codes for phone number validation.
	 *
	 * May return an array of region codes or a single region code string.
	 *
	 * @return array<int|string, mixed>|string
	 */
	public function getAllowedRegionPhoneNumber(): array|string
	{
		return $this->config['allowedRegionPhoneNumber'] ?? [];
	}


	/**
	 * Returns the absolute path to a MaxMind GeoLite2 City database, or null
	 * when none is configured. GeoIP-based features (e.g. auto-detecting the
	 * phone region from the visitor's IP) are simply skipped when this is null —
	 * the database is not bundled with this package, see the readme.
	 */
	public function getGeoLite2Path(): ?string
	{
		return $this->config['geoLite2Path'] ?? null;
	}
}
