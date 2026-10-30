<?php declare(strict_types=1);

namespace Drago\Commerce;

use Brick\Math\Exception\MathException;
use Brick\Math\RoundingMode;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use NumberFormatter;
use function is_int;


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
	 * @throws MathException
	 */
	public function moneyOf(float $amount): Money
	{
		return self::money($amount);
	}


	/**
	 * Creates a Money object from a number from the database, in the configured currency unless another is given.
	 *
	 * @throws UnknownCurrencyException
	 * @throws MathException
	 */
	public static function money(float|int $amount, ?string $currency = null): Money
	{
		return Money::of(self::decimal($amount), $currency ?? self::$currency);
	}


	/**
	 * The price after a percentage discount: price × (100 − percent) / 100 in exact decimals,
	 * rounded half up to the currency's fraction digits. The percentage is clamped to 0–100.
	 * @throws MathException
	 */
	public static function percentOff(Money $price, int $percent): Money
	{
		return $price
			->multipliedBy(100 - max(0, min(100, $percent)))
			->dividedBy(100, RoundingMode::HalfUp);
	}


	/**
	 * A number from the database (DECIMAL columns come as floats) as a decimal string, as brick/money
	 * does not accept floats. It keeps 14 significant digits, so e.g. 1 - 0.07 becomes "0.93"; counted here,
	 * not by a string cast, whose result depends on the precision setting of PHP (17 gives 19.989999999999998).
	 */
	public static function decimal(float|int $amount): string
	{
		if (is_int($amount) || $amount === 0.0) {
			return (string) (int) $amount;
		}

		$integerDigits = max(0, (int) floor(log10(abs($amount))) + 1);
		$decimal = number_format($amount, max(0, 14 - $integerDigits), '.', '');
		return str_contains($decimal, '.') ? rtrim(rtrim($decimal, '0'), '.') : $decimal;
	}


	public static function formatMoney(Money $money): string
	{
		$formatter = new NumberFormatter(self::$moneyFormat, NumberFormatter::CURRENCY);

		if (self::$moneySymbol !== '') {
			$formatter->setSymbol(NumberFormatter::CURRENCY_SYMBOL, self::$moneySymbol);
		}

		$formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, self::$moneyFractionDigits);
		$formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, self::$moneyFractionDigits);

		// What Money::formatWith() of brick/money before 0.10 did.
		return (string) $formatter->formatCurrency(
			$money->getAmount()->toFloat(),
			$money->getCurrency()->getCurrencyCode(),
		);
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
	 * Returns how many products the listing shows on one page (12 when not configured).
	 * Zero turns paging off and shows all active products.
	 */
	public function getItemsPerPage(): int
	{
		return max(0, (int) ($this->config['itemsPerPage'] ?? 12));
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
