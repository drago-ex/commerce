<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Commerce\DI;

use Drago\Commerce\Commerce;
use Nette\DI\CompilerExtension;
use Nette\Schema\Expect;
use Nette\Schema\Schema;


/**
 * Registers services and configuration for commerce extension.
 */
class CommerceExtension extends CompilerExtension
{
	/**
	 * Returns configuration schema for this extension.
	 *
	 * @return Schema Configuration schema defining expected config structure.
	 */
	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'currency' => Expect::type('string|int'),
			'moneyFormat' => Expect::string(),
			'moneySymbol' => Expect::string(),
			'defaultRegionCode' => Expect::type('array|string|false'),
			'allowedRegionPhoneNumber' => Expect::type('array|string'),
			'postCodeOnRegionPhone' => Expect::bool(),
		]);
	}


	/**
	 * Loads and registers services to DI container.
	 *
	 * @return void
	 */
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		// Load services definitions from a neon config file.
		$this->compiler->loadDefinitionsFromConfig(
			$this->loadFromFile(__DIR__ . '/services.neon')['services'],
		);

		// Register the main commerce service with the configuration passed as argument.
		$builder->addDefinition($this->prefix('commerce'))
			->setFactory(Commerce::class)
			->setArguments([(array) $this->config]);
	}
}
