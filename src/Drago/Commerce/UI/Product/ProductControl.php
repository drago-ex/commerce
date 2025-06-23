<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Commerce\UI\Product;

use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Dibi\Exception;
use Drago\Application\UI\ExtraControl;
use Drago\Attr\AttributeDetectionException;
use Drago\Commerce\Commerce;
use Drago\Commerce\Domain\Product\Product;
use Drago\Commerce\Domain\Product\ProductRepository;
use Drago\Commerce\Service\ShoppingCartSession;
use Drago\Commerce\UI\Factory;
use Nette\Application\UI\Form;
use Nette\Application\UI\Multiplier;


/**
 * @property-read ProductTemplate $template
 */
class ProductControl extends ExtraControl
{
	use Factory;

	public function __construct(
		readonly private ProductRepository $productRepository,
		readonly private ShoppingCartSession $shoppingCartSession,
		readonly private Commerce $commerce,
	) {
	}


	/**
	 * @throws Exception
	 * @throws AttributeDetectionException
	 */
	public function render(): void
	{
		$template = $this->template;
		$template->setFile(__DIR__ . '/Product.latte');
		$template->setTranslator($this->translator);
		$template->products = $this->productRepository->getAll();
		$template->render();
	}


	protected function createComponentAddToCart(): Multiplier
	{
		return new Multiplier(function (string $productId) {
			$form = $this->create($productId);
			$form->addSubmit('add', 'Add to cart');
			$form->onSuccess[] = $this->success(...);
			return $form;
		});
	}


	/**
	 * @throws UnknownCurrencyException
	 * @throws Exception
	 * @throws AttributeDetectionException
	 */
	public function success(Form $form): void
	{
		$productId = $form->getValues()['productId'];
		$row = $this->productRepository->getOne($productId);
		$product = new Product(
			id: $row->id,
			name: $row->name,
			price: Money::of($row->price, $this->commerce->moneyZero()),
		);
		$this->shoppingCartSession->addItem($product);
		$this->redirect('this');
	}
}
