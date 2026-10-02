<?php

declare(strict_types=1);

namespace Drago\Commerce\UI\Product;

use Brick\Math\RoundingMode;
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
use JsonException;
use Nette\Application\UI\Form;
use NumberFormatter;


/**
 * @property-read ProductDetailTemplate $template
 */
class ProductDetailControl extends BaseControl
{
	private int $productId;
	private ?int $selectedVariantId = null;


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
	 * (e.g. from an action parameter).
	 */
	public function setProductId(int $productId): void
	{
		$this->productId = $productId;
	}


	/**
	 * Allows pre-selecting a specific variant (e.g. from a URL parameter ?variant=123).
	 */
	public function setSelectedVariantId(?int $variantId): void
	{
		$this->selectedVariantId = $variantId;
	}


	/**
	 * @throws AttributeDetectionException
	 * @throws Exception
	 * @throws UnknownCurrencyException
	 * @throws JsonException
	 */
	public function render(): void
	{
		$entity = $this->productRepository->getOne($this->productId);
		if ($entity === null || !$entity->active) {
			$this->error('Product not found.');
		}

		$variants = $this->getVariantOptions($entity);
		$attributeGroups = $this->variantRepository->getProductAttributeGroups($entity->id);

		$selectedVariant = null;
		if ($variants !== []) {
			if ($this->selectedVariantId !== null) {
				foreach ($variants as $v) {
					if ($v->id === $this->selectedVariantId) {
						$selectedVariant = $v;
						break;
					}
				}
			}

			if ($selectedVariant === null) {
				foreach ($variants as $v) {
					if ($v->inStock()) {
						$selectedVariant = $v;
						break;
					}
				}
				$selectedVariant ??= $variants[0];
			}
		}

		$variantMatrix = [];
		foreach ($variants as $variant) {
			$variantEntity = $this->variantRepository->getOne($variant->id);
			$hasExplicitPrice = $variantEntity !== null && $variantEntity->price !== null;

			$originalPrice = null;
			$discountPercent = null;
			$discountedPrice = $variant->price;

			if (!$hasExplicitPrice && $entity->hasDiscount() && $variant->price !== null) {
				$discountRatio = max(0, min(100, $entity->discount ?? 0)) / 100;
				$discountedPrice = $variant->price->multipliedBy(1 - $discountRatio, RoundingMode::HALF_UP);
				$originalPrice = $this->formatMoney($variant->price);
				$discountPercent = $entity->getDiscountPercent();
			}

			$variantMatrix[] = [
				'id' => $variant->id,
				'sku' => $variant->sku,
				'stock' => $variant->stock,
				'inStock' => $variant->inStock(),
				'price' => $this->formatMoney($discountedPrice),
				'originalPrice' => $originalPrice,
				'discountPercent' => $discountPercent,
				'attributeValueIds' => $variant->attributeValueIds,
				'label' => $variant->getLabel(),
			];
		}

		$template = $this->template;
		$template->setFile($this->templateControl ?: __DIR__ . '/ProductDetail.latte');
		$template->setTranslator($this->translator);
		$template->product = $entity;
		$template->variants = $variants;
		$template->attributeGroups = $attributeGroups;
		$template->selectedVariant = $selectedVariant;
		$template->variantMatrixJson = json_encode($variantMatrix, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
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
	 * Formats a Money object the same way BaseTemplate::money() does.
	 */
	public function formatMoney(?Money $money): string
	{
		if ($money === null) {
			return '';
		}

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
			$defaultVariantId = $this->selectedVariantId;
			if ($defaultVariantId === null) {
				foreach ($variants as $v) {
					if ($v->inStock()) {
						$defaultVariantId = $v->id;
						break;
					}
				}
				$defaultVariantId ??= $variants[0]->id;
			}

			$form->addHidden(FactoryValues::VariantId, (string) $defaultVariantId);
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
		$productId = (int) $data->productId;
		$variantId = $data->variantId !== null && $data->variantId !== '' ? (int) $data->variantId : null;

		$entity = $this->productRepository->getOne($productId) ?? $this->error('Product not found.');

		$availableStock = $entity->stock;
		$variantLabel = null;
		$price = $this->commerce->moneyOf($entity->price);

		$applyDiscount = true;

		if ($variantId !== null) {
			$variantEntity = $this->variantRepository->getOne($variantId) ?? $this->error('Variant not found.');
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

		$event = new ProductAddedToCart($product, $product->price);
		$this->eventDispatcher->dispatch($event);

		$item = new Product(id: $entity->id, name: $entity->name, price: $event->getPrice());
		if ($applyDiscount && $event->getPrice()->isEqualTo($price)) {
			$item->setDiscount($entity->discount);
		}

		$this->shoppingCartSession->addItem($item, $data->amount, variantId: $variantId, variantLabel: $variantLabel);

		$this->getPresenter()->flashMessage('The product has been added to the cart.', Alert::Success);
		$this->getPresenter()->redrawControl('message');
		$this->getPresenter()->redrawControl('cart');
	}
}
