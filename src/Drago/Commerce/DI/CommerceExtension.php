<?php

declare(strict_types=1);

namespace Drago\Commerce\DI;

use Drago\Commerce\Commerce;
use Nette\DI\CompilerExtension;
use Nette\Schema\Expect;
use Nette\Schema\Schema;


/**
 * DI extension registering the Commerce service and loading its configuration.
 */
class CommerceExtension extends CompilerExtension
{
	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'currency' => Expect::type('string|int'),
			'moneyFormat' => Expect::string(),
			'moneySymbol' => Expect::string(),
			'moneyFractionDigits' => Expect::int(),
			'defaultRegionCode' => Expect::type('array|string|false'),
			'allowedRegionPhoneNumber' => Expect::type('array|string'),
			'postCodeOnRegionPhone' => Expect::bool(),
		]);
	}


	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		$this->compiler->loadDefinitionsFromConfig(
			$this->loadFromFile(__DIR__ . '/services.neon')['services'],
		);

		$builder->addDefinition($this->prefix('commerce'))
			->setFactory(Commerce::class)
			->setArguments([(array) $this->config]);
	}
}
