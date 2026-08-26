<?php

declare(strict_types=1);

namespace Drago\Commerce\UI;

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


	/**
	 * Creates a new form with translator configured.
	 */
	public function create(): BaseForm
	{
		$form = new BaseForm;
		$form->setTranslator($this->translator);
		return $form;
	}


	/**
	 * Creates a form with a hidden product ID field.
	 */
	public function addHiddenProductId(string $productId): BaseForm
	{
		$form = $this->create();
		$form->addHidden(FactoryValues::ProductId, $productId)
			->addRule($form::Integer);

		return $form;
	}


	/**
	 * Creates a form for changing the quantity of a cart item.
	 */
	public function addChangeAmountInCart(string $productId): BaseForm
	{
		$form = $this->addHiddenProductId($productId);
		$form->addInteger(FactoryValues::Amount)
			->setDefaultValue(1)
			->setHtmlAttribute('autocomplete', 'off')
			->addRule($form::Min, arg: 1)
			->addRule($form::Integer)
			->setRequired();

		return $form;
	}


	public function addDiscountCode(): BaseForm
	{
		$form = $this->create();
		$form->addTextInput(FactoryValues::Code, 'Discount code')
			->setRequired('Please enter a discount code.')
			->setHtmlAttribute('autocomplete', 'off')
			->setHtmlAttribute('placeholder', 'Enter discount code')
			->setHtmlAttribute('aria-label', 'Discount code');
		$form->addSubmit('apply', 'Apply');

		return $form;
	}
}
