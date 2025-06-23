<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Commerce\Data;

use GeoIp2\Database\Reader;
use GeoIp2\Exception\GeoIp2Exception;
use GeoIp2\Model\City;
use MaxMind\Db\Reader\InvalidDatabaseException;


class ReaderGeoLite
{
	/**
	 * @throws InvalidDatabaseException
	 */
	private function reader(): Reader
	{
		return new Reader(__DIR__ . '/GeoLite2-City.mmdb');
	}


	/**
	 * @throws InvalidDatabaseException
	 */
	public function getCity(string $ip = '127.0.0.0'): ?City
	{
		try {
			return $this->reader()->city($ip);
		} catch (GeoIp2Exception $e) {
			return null;
		}
	}


	/**
	 * @throws InvalidDatabaseException
	 */
	public function getCountryIsoCode(string $ip = '94.113.197.225'): ?string
	{
		$city = $this->getCity($ip);
		return $city?->country?->isoCode ?? null;
	}
}
