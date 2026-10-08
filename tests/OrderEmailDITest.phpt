<?php

declare(strict_types=1);

namespace Drago\Commerce\Tests;

use Drago\Commerce\DI\CommerceExtension;
use Drago\Commerce\Event\OrderPlaced;
use Drago\Commerce\Mail\OrderEmail;
use Drago\Commerce\Mail\OrderEmailListener;
use Nette\DI\Compiler;
use Nette\DI\Definitions\ServiceDefinition;
use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$disabledCompiler = new Compiler;
$disabledCompiler->addExtension('commerce', new CommerceExtension);
$disabledCompiler->processExtensions();
Assert::false($disabledCompiler->getContainerBuilder()->hasDefinition('commerce.orderEmail'));

$compiler = new Compiler;
$compiler->addExtension('commerce', new CommerceExtension);
$compiler->addConfig([
	'commerce' => [
		'orderEmail' => [
			'from' => 'Shop <shop@example.test>',
			'mailer' => 'mail.mailer',
			'storeName' => 'Test shop',
			'storeEmail' => 'support@example.test',
		],
	],
]);
$compiler->processExtensions();

$builder = $compiler->getContainerBuilder();
$mailer = $builder->getDefinition('commerce.orderEmail');
Assert::same(OrderEmail::class, $mailer->getType());
Assert::same('Shop <shop@example.test>', $mailer->getCreator()->arguments['sender']);
Assert::same('mail.mailer', $mailer->getCreator()->arguments['mailer']->getValue());

$listener = $builder->getDefinition('commerce.orderEmailListener');
Assert::same(OrderEmailListener::class, $listener->getType());

$dispatcher = $builder->getDefinition('eventDispatcher');
Assert::true($dispatcher instanceof ServiceDefinition);
$setup = $dispatcher->getSetup();
Assert::same(OrderPlaced::class, $setup[array_key_last($setup)]->arguments[0]);
