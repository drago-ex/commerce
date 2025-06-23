<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Commerce\UI;

use Nette\Application\UI\Form;


/**
 * Trait Factory
 * Provides factory methods to create forms for product operations.
 */
trait Factory
{
	/**
	 * Create form with product ID.
	 *
	 * @param string $productId
	 * @return Form
	 */
	public function create(string $productId): Form
	{
		$form = new Form;
		$form->addHidden(FactoryData::ProductId, (int) $productId)
			->addRule($form::Integer);

		return $form;
	}


	/**
	 * Create form with product ID and amount.
	 *
	 * @param string $productId
	 * @return Form
	 */
	public function createWithAmount(string $productId): Form
	{
		$form = $this->create($productId);
		$form->addInteger(FactoryData::Amount)
			->setDefaultValue(1)
			->setHtmlAttribute('autocomplete', 'off')
			->addRule($form::Min, null, 1)
			->addRule($form::Integer)
			->setRequired();

		return $form;
	}
}
