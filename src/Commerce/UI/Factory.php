<?php

declare(strict_types=1);

namespace Drago\Commerce\UI;

use Drago\Form\Autocomplete;
use Nette\Localization\Translator;


/** Creates Commerce forms and applies the configured translator. */
readonly class Factory
{
	public function __construct(
		private ?Translator $translator = null,
	) {
	}


	/**
	 * Creates a new form with translator configured.
	 */
	public function create(?Translator $translator = null): BaseForm
	{
		$form = new BaseForm;
		$translator ??= $this->translator;
		if ($translator !== null) {
			$form->setTranslator($translator);
		}
		return $form;
	}


	/**
	 * Creates a form with a hidden product ID field, and — when a variant
	 * was chosen — a hidden variant ID field alongside it.
	 */
	public function addHiddenProductId(
		string $productId,
		?int $variantId = null,
		?Translator $translator = null,
	): BaseForm
	{
		$form = $this->create($translator);
		$form->addHidden(FactoryValues::ProductId, $productId)
			->addRule($form::Integer);

		if ($variantId !== null) {
			$form->addHidden(FactoryValues::VariantId, (string) $variantId)
				->addRule($form::Integer);
		}

		return $form;
	}


	/**
	 * Creates a form for changing the quantity of a cart item.
	 */
	public function addChangeAmountInCart(
		string $productId,
		?int $variantId = null,
		?Translator $translator = null,
	): BaseForm
	{
		$form = $this->addHiddenProductId($productId, $variantId, $translator);
		$form->addIntegerInput(FactoryValues::Amount)
			->setAutocomplete(Autocomplete::Off)
			->setDefaultValue(1)
			->setMin(1)
			->addRule($form::Integer)
			->setRequired();

		return $form;
	}


	public function addDiscountCode(?Translator $translator = null): BaseForm
	{
		$form = $this->create($translator);
		$form->addTextInput(FactoryValues::Code, 'Discount code')
			->setRequired('Please enter a discount code.')
			->setAutocomplete(Autocomplete::Off)
			->setPlaceholder('Enter discount code')
			->setHtmlAttribute('aria-label', ($translator ?? $this->translator)?->translate('Discount code') ?? 'Discount code');
		$form->addSubmit('apply', 'Apply');

		return $form;
	}
}
