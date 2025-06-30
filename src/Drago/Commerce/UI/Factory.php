<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Commerce\UI;

use App\Commerce\UI\BaseForm;
use Nette\Localization\Translator;


/**
 * Factory class for creating instances of FormBase with necessary configurations.
 */
readonly class Factory
{
	public function __construct(
		private Translator $translator,
	) {
	}


	public function create(): BaseForm
	{
		$form = new BaseForm;
		$form->setTranslator($this->translator);
		return $form;
	}


	public function addHiddenProductId(string $productId): BaseForm
	{
		$form = $this->create();
		$form->addHidden(FactoryData::ProductId, $productId)
			->addRule($form::Integer);

		return $form;
	}


	public function addChangeAmountInCart(string $productId): BaseForm
	{
		$form = $this->addHiddenProductId($productId);
		$form->addInteger(FactoryData::Amount)
			->setDefaultValue(1)
			->setHtmlAttribute('autocomplete', 'off')
			->addRule($form::Min, arg: 1)
			->addRule($form::Integer)
			->setRequired();

		return $form;
	}
}
