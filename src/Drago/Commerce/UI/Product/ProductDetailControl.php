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
use Drago\Commerce\Domain\Product\ProductVariantMapper;
use Drago\Commerce\Domain\Product\ProductVariantOption;
use Drago\Commerce\Domain\Product\ProductVariantRepository;
use Drago\Commerce\Event\EventDispatcher;
use Drago\Commerce\Event\ProductAddedToCart;
use Drago\Commerce\Service\ShoppingCartSession;
use Drago\Commerce\UI\BaseControl;
use Drago\Commerce\UI\BaseForm;
use Drago\Commerce\UI\FactoryValues;
use Nette\Application\UI\Form;
use NumberFormatter;


/**
 * @property-read ProductDetailTemplate $template
 */
class ProductDetailControl extends BaseControl
{
	private int $productId;


	public function __construct(
		private readonly ProductRepository $productRepository,
		private readonly ProductVariantRepository $variantRepository,
		private readonly ProductVariantMapper $variantMapper,
		private readonly ShoppingCartSession $shoppingCartSession,
		private readonly Commerce $commerce,
		private readonly EventDispatcher $eventDispatcher,
	) {
	}


	/**
	 * Which product this instance shows. Set by the presenter before render
	 * (e.g. from an action parameter) — same pattern as setSteps()/
	 * setCurrentStep() elsewhere in this package.
	 */
	public function setProductId(int $productId): void
	{
		$this->productId = $productId;
	}


	/**
	 * @throws AttributeDetectionException
	 * @throws Exception
	 * @throws UnknownCurrencyException
	 */
	public function render(): void
	{
		$entity = $this->productRepository->getOne($this->productId);
		if ($entity === null || !$entity->active) {
			$this->error('Product not found.');
		}

		$template = $this->template;
		$template->setFile($this->templateControl ?: __DIR__ . '/ProductDetail.latte');
		$template->setTranslator($this->translator);
		$template->product = $entity;
		$template->variants = $this->getVariantOptions($entity);
		$template->render();
	}


	/**
	 * @return list<ProductVariantOption>
	 * @throws AttributeDetectionException
	 * @throws Exception
	 * @throws UnknownCurrencyException
	 */
	private function getVariantOptions(ProductEntity $entity): array
	{
		$options = [];
		foreach ($this->variantRepository->getForProduct($entity->id) as $variantEntity) {
			$options[] = $this->variantMapper->map($variantEntity, $entity->price);
		}
		return $options;
	}


	/**
	 * Formats a Money object the same way BaseTemplate::money() does, so a
	 * price shown inside a form label (e.g. the variant select) matches
	 * prices shown elsewhere on the page.
	 */
	private function formatMoney(Money $money): string
	{
		$formatter = new NumberFormatter(Commerce::$moneyFormat, NumberFormatter::CURRENCY);

		if (Commerce::$moneySymbol) {
			$formatter->setSymbol(NumberFormatter::CURRENCY_SYMBOL, Commerce::$moneySymbol);
		}

		$formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, Commerce::$moneyFractionDigits);
		$formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, Commerce::$moneyFractionDigits);

		return $money->formatWith($formatter);
	}


	/**
	 * @throws AttributeDetectionException
	 * @throws Exception
	 * @throws UnknownCurrencyException
	 */
	protected function createComponentAddToCart(): BaseForm
	{
		$entity = $this->productRepository->getOne($this->productId) ?? $this->error('Product not found.');
		$variants = $this->getVariantOptions($entity);

		$form = new BaseForm;
		if ($this->translator !== null) {
			$form->setTranslator($this->translator);
		}

		$form->addHidden(FactoryValues::ProductId, (string) $this->productId)
			->addRule($form::Integer);

		if ($variants !== []) {
			$items = [];
			foreach ($variants as $variant) {
				$suffix = $variant->inStock() ? '' : ' (sold out)';
				$items[$variant->id] = $variant->getLabel() . ' — ' . $this->formatMoney($variant->price) . $suffix;
			}

			$form->addSelect(FactoryValues::VariantId, 'Variant', $items)
				->setPrompt('Please choose a variant')
				->setRequired('Please choose a variant.');
		}

		$form->addIntegerInput(FactoryValues::Amount)
			->setDefaultValue(1)
			->setMin(1)
			->addRule($form::Integer)
			->setRequired();

		$form->addSubmit('add', 'Add to cart');
		$form->onSuccess[] = $this->success(...);

		return $form;
	}


	/**
	 * @throws AttributeDetectionException
	 * @throws Exception
	 * @throws UnknownCurrencyException
	 * @throws MoneyMismatchException
	 */
	public function success(Form $form, FactoryValues $data): void
	{
		$entity = $this->productRepository->getOne($data->productId) ?? $this->error('Product not found.');

		$availableStock = $entity->stock;
		$variantLabel = null;
		$price = $this->commerce->moneyOf($entity->price);

		// A variant's own price (if set) is treated as already final — it
		// doesn't get the product's % discount layered on top of it. A
		// variant that inherits the product's price (no override) is
		// discounted normally, same as a product without variants.
		$applyDiscount = true;

		if ($data->variantId !== null) {
			$variantEntity = $this->variantRepository->getOne($data->variantId) ?? $this->error('Variant not found.');
			$availableStock = $variantEntity->stock;
			$variantLabel = implode(', ', $this->variantRepository->getLabels($variantEntity->id));

			if ($variantEntity->price !== null) {
				$price = $this->commerce->moneyOf($variantEntity->price);
				$applyDiscount = false;
			}
		}

		if ($availableStock < $data->amount) {
			$this->getPresenter()->flashMessage("The product $entity->name is only $availableStock pcs in stock.", Alert::Danger);
			$this->getPresenter()->redrawControl('message');
			return;
		}

		$product = new Product(id: $entity->id, name: $entity->name, price: $price);

		// Same extension point as ProductControl::success() — a listener
		// may still override the final price via the event.
		$event = new ProductAddedToCart($product, $product->price);
		$this->eventDispatcher->dispatch($event);

		$item = new Product(id: $entity->id, name: $entity->name, price: $event->getPrice());
		if ($applyDiscount && $event->getPrice()->isEqualTo($price)) {
			$item->setDiscount($entity->discount);
		}

		$this->shoppingCartSession->addItem($item, $data->amount, variantId: $data->variantId, variantLabel: $variantLabel);

		$this->getPresenter()->flashMessage('The product has been added to the cart.', Alert::Success);
		$this->getPresenter()->redrawControl('message');
		$this->getPresenter()->redrawControl('cart');
	}
}
