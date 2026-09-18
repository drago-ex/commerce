<?php

declare(strict_types=1);

namespace Drago\Commerce\UI;

use Drago\Form\Autocomplete;
use Nette\Localization\Translator;


/**
 * Factory class for creating instances of FormBase with necessary configurations.
 */
readonly class Factory
{
	public function __construct(
		private ?Translator $translator = null,
	) {
	}


	/**
	 * Creates a new form with translator configured.
	 */
	public function create(): BaseForm
	{
		$form = new BaseForm;
		if ($this->translator !== null) {
			$form->setTranslator($this->translator);
		}
		return $form;
	}


	/**
	 * Creates a form with a hidden product ID field, and — when a variant
	 * was chosen — a hidden variant ID field alongside it.
	 */
	public function addHiddenProductId(string $productId, ?int $variantId = null): BaseForm
	{
		$form = $this->create();
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
	public function addChangeAmountInCart(string $productId, ?int $variantId = null): BaseForm
	{
		$form = $this->addHiddenProductId($productId, $variantId);
		$form->addIntegerInput(FactoryValues::Amount)
			->setAutocomplete(Autocomplete::Off)
			->setDefaultValue(1)
			->setMin(1)
			->addRule($form::Integer)
			->setRequired();

		return $form;
	}


	public function addDiscountCode(): BaseForm
	{
		$form = $this->create();
		$form->addTextInput(FactoryValues::Code, 'Discount code')
			->setRequired('Please enter a discount code.')
			->setAutocomplete(Autocomplete::Off)
			->setPlaceholder('Enter discount code')
			->setHtmlAttribute('aria-label', 'Discount code');
		$form->addSubmit('apply', 'Apply');

		return $form;
	}
}
