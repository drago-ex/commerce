<?php

declare(strict_types=1);

namespace Drago\Commerce\Data;

use Drago\Commerce\Commerce;
use GeoIp2\Database\Reader;
use GeoIp2\Model\City;
use MaxMind\Db\Reader\InvalidDatabaseException;


/**
 * Provides GeoIP2 lookups using a MaxMind GeoLite2 City database.
 *
 * The database file is NOT bundled with this package (it's a large binary
 * with its own licensing terms) — configure its path via the 'geoLite2Path'
 * commerce option. When unset, all lookups simply return null so features
 * that use this (e.g. phone region auto-detection) degrade gracefully.
 */
class ReaderGeoLite
{
	public function __construct(
		private readonly Commerce $commerce,
	) {
	}


	/**
	 * Opens the configured GeoLite2 City database reader, or null when no
	 * path is configured.
	 *
	 * @throws InvalidDatabaseException
	 */
	private function reader(): ?Reader
	{
		$path = $this->commerce->getGeoLite2Path();
		return $path !== null ? new Reader($path) : null;
	}


	/**
	 * Returns city data for the given IP address, or null when unavailable
	 * (no database configured, unreadable file, or lookup failure).
	 */
	public function getCity(string $ip): ?City
	{
		try {
			return $this->reader()?->city($ip);
		} catch (\Throwable $e) {
			return null;
		}
	}


	/**
	 * Returns the ISO country code for the given IP address, or null if unavailable.
	 */
	public function getCountryIsoCode(string $ip): ?string
	{
		$city = $this->getCity($ip);
		return $city?->country->isoCode ?? null;
	}
}
