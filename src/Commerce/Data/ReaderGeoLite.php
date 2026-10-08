<?php

declare(strict_types=1);

namespace Drago\Commerce\Data;

use Drago\Commerce\Commerce;
use GeoIp2\Database\Reader;
use GeoIp2\Model\City;
use Throwable;
use Tracy\Debugger;


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
	private ?Reader $reader = null;

	private bool $failed = false;


	public function __construct(
		private readonly Commerce $commerce,
	) {
	}


	/**
	 * Opens the configured GeoLite2 City database once and reuses the reader.
	 * Returns null when no path is configured. A database that cannot be opened
	 * (wrong path, geoip2/geoip2 not installed) is logged once and then skipped,
	 * so customers can still fill in the form.
	 */
	private function reader(): ?Reader
	{
		if ($this->reader !== null || $this->failed) {
			return $this->reader;
		}

		$path = $this->commerce->getGeoLite2Path();
		if ($path === null) {
			return null;
		}

		try {
			$this->reader = new Reader($path);
		} catch (Throwable $e) {
			$this->failed = true;
			try {
				Debugger::log($e, Debugger::ERROR);
			} catch (Throwable) {
				// Logging must never break the form.
			}
		}

		return $this->reader;
	}


	/**
	 * Returns city data for the given IP address, or null when unavailable
	 * (no database configured, unreadable file, or lookup failure).
	 */
	public function getCity(string $ip): ?City
	{
		try {
			return $this->reader()?->city($ip);
		} catch (Throwable) {
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
