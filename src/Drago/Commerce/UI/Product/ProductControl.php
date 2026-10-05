<?php

declare(strict_types=1);

namespace Drago\Commerce\UI\Product;

use Brick\Money\Exception\MoneyMismatchException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Dibi\Exception;
use Drago\Application\UI\Alert;
use Drago\Attr\AttributeDetectionException;
use Drago\Commerce\Commerce;
use Drago\Commerce\Domain\Product\Product;
use Drago\Commerce\Domain\Product\ProductEntity;
use Drago\Commerce\Domain\Product\ProductRepository;
use Drago\Commerce\Event\EventDispatcher;
use Drago\Commerce\Event\ProductAddedToCart;
use Drago\Commerce\Service\ShoppingCartSession;
use Drago\Commerce\UI\BaseControl;
use Drago\Commerce\UI\BaseForm;
use Drago\Commerce\UI\Factory;
use Nette\Application\UI\Form;
use Nette\Application\UI\Multiplier;


/**
 * @property-read ProductTemplate $template
 */
class ProductControl extends BaseControl
{
	public function __construct(
		private readonly ProductRepository $productRepository,
		private readonly ShoppingCartSession $shoppingCartSession,
		private readonly Commerce $commerce,
		private readonly Factory $factory,
		private readonly EventDispatcher $eventDispatcher,
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


	/**
	 * Creates an add-to-cart form for each product using Nette Multiplier.
	 *
	 * @return Multiplier<BaseForm>
	 */
	protected function createComponentAddToCart(): Multiplier
	{
		return new Multiplier(function (string $productId) {
			$form = $this->factory->addHiddenProductId($productId);
			$form->setTranslator($this->translator);
			$form->addSubmit('add', 'Add to cart');
			$form->onSuccess[] = $this->success(...);
			return $form;
		});
	}


	/**
	 * Handles successful add-to-cart submissions by validating the product,
	 * calculating its final price, and adding it to the cart.
	 *
	 * @throws Exception
	 * @throws AttributeDetectionException
	 * @throws UnknownCurrencyException
	 * @throws MoneyMismatchException
	 */
	public function success(Form $form, ProductData $data): void
	{
		$entity = $this->productRepository->getOne($data->productId);
		$this->validateProduct($entity);

		if ($entity === null) {
			return;
		}

		$product = $this->createProductEntity($entity, $this->commerce->moneyOf($entity->price));

		$event = new ProductAddedToCart($product, $product->price);
		$this->eventDispatcher->dispatch($event);

		$item = $this->createProductEntity($entity, $event->getPrice());

		$this->shoppingCartSession->addItem($item);
		$this->getPresenter()->flashMessage('The product has been added to the cart.', Alert::Success);

		if ($this->isAjax()) {
			$this->getPresenter()->redrawControl('message');
			$this->getPresenter()->redrawControl('cart');
		} else {
			$this->getPresenter()->redirect('this');
		}
	}


	/**
	 * Validates product existence, activity, and stock, redirecting with a
	 * flash message when the product cannot be purchased.
	 */
	private function validateProduct(?ProductEntity $product): void
	{
		if (!$product || !$product->active) {
			$this->getPresenter()->flashMessage('The product does not exist or is not active.', Alert::Danger);
			$this->getPresenter()->redirect('this');
		}

		if ($product->stock <= 0) {
			$this->getPresenter()->flashMessage('The product is out of stock.', Alert::Warning);
			$this->getPresenter()->redirect('this');
		}
	}


	/**
	 * Creates a Product domain object from an entity and the given price.
	 *
	 * @throws MoneyMismatchException
	 */
	private function createProductEntity(ProductEntity $entity, Money $price): Product
	{
		$product = new Product(
			id: $entity->id,
			name: $entity->name,
			price: $price,
		);

		// Only apply the standard % discount when nothing else already produced
		// a custom final price via the ProductAddedToCart event — otherwise
		// getDiscountedPrice() would discount an already-final price again.
		if ($price->isEqualTo($this->commerce->moneyOf($entity->price))) {
			$product->setDiscount($entity->discount);
		}

		return $product;
	}
}
