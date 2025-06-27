<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Commerce\UI\Product;

use Brick\Money\Currency;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Dibi\Exception;
use Drago\Application\UI\Alert;
use Drago\Attr\AttributeDetectionException;
use Drago\Commerce\Commerce;
use Drago\Commerce\Domain\Product\Product;
use Drago\Commerce\Domain\Product\ProductEntity;
use Drago\Commerce\Domain\Product\ProductRepository;
use Drago\Commerce\Service\ShoppingCartSession;
use Drago\Commerce\UI\BaseControl;
use Drago\Commerce\UI\Factory;
use Nette\Application\UI\Form;
use Nette\Application\UI\Multiplier;


/**
 * @property-read ProductTemplate $template
 */
class ProductControl extends BaseControl
{
	public function __construct(
		readonly private ProductRepository $productRepository,
		readonly private ShoppingCartSession $shoppingCartSession,
		readonly private Commerce $commerce,
		readonly private Factory $factory,
	) {
	}


	/**
	 * @throws Exception
	 * @throws AttributeDetectionException
	 */
	public function render(): void
	{
		$template = $this->template;
		$template->setFile($this->templateControl ?: __DIR__ . '/Product.latte');
		$template->setTranslator($this->translator);
		$template->products = $this->productRepository->getAll();
		$template->render();
	}


	protected function createComponentAddToCart(): Multiplier
	{
		return new Multiplier(function (string $productId) {
			$form = $this->factory->create($productId);
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
	public function success(Form $form, ProductData $data): void
	{
		$product = $this->productRepository->getOne($data->productId);

		if (!$product || !$product->active) {
			$this->getPresenter()->flashMessage('The product does not exist or is not active.', Alert::Danger);
			$this->getPresenter()->redirect('this');
		}


		if ($product->stock <= 0) {
			$this->getPresenter()->flashMessage('The product is out of stock.', Alert::Warning);
			$this->getPresenter()->redirect('this');
		}

		$product = $this->getProduct($data->productId);
		$item = new Product(
			id: $product->id,
			name: $product->name,
			price: Money::of($product->price, $this->getCurrency()),
		);
		$this->shoppingCartSession->addItem($item);
		$this->getPresenter()->flashMessage('The product has been added to the cart.', Alert::Success);
		$this->redirect('this');
	}


	private function getCurrency(): Currency
	{
		return $this->commerce->moneyZero()
			->getCurrency();
	}


	/**
	 * @throws Exception
	 * @throws AttributeDetectionException
	 */
	private function getProduct(int $productId): array|ProductEntity
	{
		return $this->productRepository->getOne($productId);
	}
}
