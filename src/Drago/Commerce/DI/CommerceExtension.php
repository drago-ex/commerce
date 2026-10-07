<?php

declare(strict_types=1);

namespace Drago\Commerce\DI;

use Drago\Commerce\Commerce;
use Drago\Commerce\Event\OrderPlaced;
use Drago\Commerce\Mail\OrderConfirmationListener;
use Drago\Commerce\Mail\OrderConfirmationMailer;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\Reference;
use Nette\DI\Definitions\ServiceDefinition;
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
			'itemsPerPage' => Expect::int()->min(0),
			'geoLite2Path' => Expect::string()->nullable()->default(null),
			'orderEmail' => Expect::structure([
				'from' => Expect::string()->nullable()->default(null),
				'mailer' => Expect::string()->default('mail.mailer'),
				'templateFile' => Expect::string()->nullable()->default(null),
				'storeName' => Expect::string()->default(''),
				'storeEmail' => Expect::string()->default(''),
			]),
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

		$orderEmail = (array) ((array) $this->config)['orderEmail'];
		$sender = $orderEmail['from'] ?? null;
		if (is_string($sender)) {
			$builder->addDefinition($this->prefix('orderConfirmationMailer'))
				->setType(OrderConfirmationMailer::class)
				->setArguments([
					'mailer' => new Reference(ltrim($orderEmail['mailer'], '@')),
					'sender' => $sender,
					'templateFile' => $orderEmail['templateFile'],
					'storeName' => $orderEmail['storeName'],
					'storeEmail' => $orderEmail['storeEmail'],
				]);

			$builder->addDefinition($this->prefix('orderConfirmationListener'))
				->setType(OrderConfirmationListener::class)
				->setArguments([
					'mailer' => new Reference($this->prefix('orderConfirmationMailer')),
				]);

			$eventDispatcher = $builder->getDefinition('eventDispatcher');
			if (!$eventDispatcher instanceof ServiceDefinition) {
				throw new \LogicException('The commerce event dispatcher must be a service definition.');
			}

			$eventDispatcher->addSetup('addListener', [
				OrderPlaced::class,
				new Reference($this->prefix('orderConfirmationListener')),
			]);
		}
	}
}
