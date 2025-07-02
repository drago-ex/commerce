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
		readonly private EventDispatcher $eventDispatcher,
	) {
	}


	/**
	 * Render product listing template
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
	 * Create add-to-cart forms for each product using Nette Multiplier
	 */
	protected function createComponentAddToCart(): Multiplier
	{
		return new Multiplier(function (string $productId) {
			$form = $this->factory->addHiddenProductId($productId);
			$form->addSubmit('add', 'Add to cart');
			$form->onSuccess[] = $this->success(...);
			return $form;
		});
	}


	/**
	 * Handle add-to-cart form success
	 * Validates product, calculates final price, adds item to cart
	 *
	 * @throws Exception
	 * @throws AttributeDetectionException
	 * @throws UnknownCurrencyException
	 */
	public function success(Form $form, ProductData $data): void
	{
		$entity = $this->productRepository->getOne($data->productId);
		$this->validateProduct($entity);

		// Create domain model Product with original price for event dispatch
		$productForEvent = $this->createProductFromEntity($entity, $this->commerce->moneyOf($entity->price));

		// Calculate final price after discounts/modifications via event
		$finalPrice = $this->calculateFinalPrice($productForEvent);

		// Create product item for cart with final price
		$item = $this->createProductFromEntity($entity, $finalPrice);

		$this->shoppingCartSession->addItem($item);
		$this->getPresenter()->flashMessage('The product has been added to the cart.', Alert::Success);
		$this->redirect('this');
	}


	/**
	 * Validate product existence, activity and stock
	 * Redirects with a flash message if invalid
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
	 * Calculate final product price using event dispatching
	 */
	private function calculateFinalPrice(Product $product): Money
	{
		$originalPrice = $product->price;
		$event = new ProductAddedToCart($product, $originalPrice);
		$this->eventDispatcher->dispatch($event);
		return $event->getFinalPrice();
	}


	/**
	 * Create a Product domain object from entity and given price
	 */
	private function createProductFromEntity(ProductEntity $entity, Money $price): Product
	{
		$product = new Product(
			id: $entity->id,
			name: $entity->name,
			price: $price,
		);

		$product->setDiscount($entity->discount);
		return $product;
	}
}
