<?php

declare(strict_types=1);

namespace Drago\Commerce\Data;

use GeoIp2\Database\Reader;
use GeoIp2\Exception\GeoIp2Exception;
use GeoIp2\Model\City;
use MaxMind\Db\Reader\InvalidDatabaseException;


/**
 * Provides GeoIP2 lookups using the MaxMind GeoLite2 City database.
 */
class ReaderGeoLite
{
	/**
	 * Opens the GeoLite2 City database reader.
	 *
	 * @throws InvalidDatabaseException
	 */
	private function reader(): Reader
	{
		return new Reader(__DIR__ . '/GeoLite2-City.mmdb');
	}


	/**
	 * Returns city data for the given IP address, or null on lookup failure.
	 *
	 * @throws InvalidDatabaseException
	 */
	public function getCity(string $ip): ?City
	{
		try {
			return $this->reader()->city($ip);
		} catch (GeoIp2Exception $e) {
			return null;
		}
	}


	/**
	 * Returns the ISO country code for the given IP address, or null if unavailable.
	 *
	 * @throws InvalidDatabaseException
	 */
	public function getCountryIsoCode(string $ip): ?string
	{
		$city = $this->getCity($ip);
		return $city?->country->isoCode ?? null;
	}
}
