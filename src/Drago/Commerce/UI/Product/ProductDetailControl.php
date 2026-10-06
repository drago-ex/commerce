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
use Drago\Commerce\Domain\Product\PriceResolver;
use Drago\Commerce\Domain\Product\ProductEntity;
use Drago\Commerce\Domain\Product\ProductImageRepository;
use Drago\Commerce\Domain\Product\ProductRepository;
use Drago\Commerce\Domain\Product\ProductVariantMapper;
use Drago\Commerce\Domain\Product\ProductVariantOption;
use Drago\Commerce\Domain\Product\ProductVariantRepository;
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

	/** @var list<ProductVariantOption>|null */
	private ?array $variantOptions = null;


	public function __construct(
		private readonly ProductRepository $productRepository,
		private readonly ProductImageRepository $productImageRepository,
		private readonly ProductVariantRepository $variantRepository,
		private readonly ProductVariantMapper $variantMapper,
		private readonly ShoppingCartSession $shoppingCartSession,
		private readonly PriceResolver $priceResolver,
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
		$selectedVariant = $this->pickSelectedVariant($variants);

		$variantMatrix = [];
		foreach ($variants as $variant) {
			$pricing = $this->resolvePrice($entity, $variant);

			$variantMatrix[] = [
				'id' => $variant->id,
				'sku' => $variant->sku,
				'stock' => $variant->stock,
				'inStock' => $variant->inStock(),
				'price' => $this->formatMoney($pricing['price']),
				'originalPrice' => $pricing['original'] !== null ? $this->formatMoney($pricing['original']) : null,
				'discountPercent' => $pricing['percent'] > 0 ? $pricing['percent'] : null,
				'attributeValueIds' => $variant->attributeValueIds,
				'label' => $variant->getLabel(),
			];
		}

		$pricing = $this->resolvePrice($entity, $selectedVariant);

		$template = $this->template;
		$template->setFile($this->templateControl ?: __DIR__ . '/ProductDetail.latte');
		$template->setTranslator($this->translator);
		$template->product = $entity;
		$template->images = array_values(array_unique(array_filter(
			[$entity->photo, ...$this->productImageRepository->getForProduct($entity->id)],
			static fn(string $image): bool => $image !== '',
		)));
		if ($template->images === []) {
			$template->images = ['https://placehold.co/500x400?text=%20&bg=f4f6f8'];
		}
		$template->variants = $variants;
		$template->attributeGroups = $attributeGroups;
		$template->selectedVariant = $selectedVariant;
		$template->price = $pricing['price'];
		$template->originalPrice = $pricing['original'];
		$template->discountPercent = $pricing['percent'];
		$template->variantMatrixJson = json_encode($variantMatrix, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
		$template->render();
	}


	/**
	 * Picks the variant to show as selected: the requested one if it exists,
	 * otherwise the first one in stock, otherwise the first one.
	 *
	 * @param list<ProductVariantOption> $variants
	 */
	private function pickSelectedVariant(array $variants): ?ProductVariantOption
	{
		$requestedId = $this->getRequestedVariantId();
		foreach ($variants as $variant) {
			if ($variant->id === $requestedId) {
				return $variant;
			}
		}

		foreach ($variants as $variant) {
			if ($variant->inStock()) {
				return $variant;
			}
		}

		return $variants[0] ?? null;
	}


	/**
	 * The variant to preselect: the one set by the presenter, otherwise the
	 * one in the `variant` URL parameter, which the detail page keeps up to
	 * date as the customer picks a variant, so a reload or a shared link
	 * shows the same variant.
	 */
	private function getRequestedVariantId(): ?int
	{
		if ($this->selectedVariantId !== null) {
			return $this->selectedVariantId;
		}

		$value = $this->getPresenter()->getParameter('variant');
		return is_string($value) && ctype_digit($value) ? (int) $value : null;
	}


	/**
	 * Returns the price a customer pays for the product, or for its variant if
	 * one is given. The product's percentage discount applies unless the
	 * variant has its own price.
	 *
	 * @return array{price: Money, original: ?Money, percent: int}
	 * @throws UnknownCurrencyException
	 */
	private function resolvePrice(ProductEntity $entity, ?ProductVariantOption $variant): array
	{
		$base = $variant->price ?? $entity->getPrice();

		if ($variant?->priceOverridden === true || !$entity->hasDiscount()) {
			return ['price' => $base, 'original' => null, 'percent' => 0];
		}

		$ratio = max(0, min(100, $entity->getDiscountPercent())) / 100;

		return [
			'price' => $base->multipliedBy(1 - $ratio, RoundingMode::HALF_UP),
			'original' => $base,
			'percent' => $entity->getDiscountPercent(),
		];
	}


	/**
	 * @return list<ProductVariantOption>
	 * @throws AttributeDetectionException
	 * @throws Exception
	 * @throws UnknownCurrencyException
	 */
	private function getVariantOptions(ProductEntity $entity): array
	{
		return $this->variantOptions ??= $this->variantMapper->mapMany(
			$entity->id,
			$this->variantRepository->getForProduct($entity->id),
			$entity->price,
		);
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

		$selected = $this->pickSelectedVariant($variants);
		if ($selected !== null) {
			$form->addHidden(FactoryValues::VariantId, (string) $selected->id);
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
		$amount = (int) $data->amount;

		$entity = $this->productRepository->getOne($productId) ?? $this->error('Product not found.');
		if (!$entity->active) {
			$this->getPresenter()->flashMessage('The product does not exist or is not active.', Alert::Danger);
			$this->getPresenter()->redirect('this');
		}

		if ($amount < 1) {
			return;
		}

		$availableStock = $entity->stock;
		$variantLabel = null;
		$variantEntity = null;

		if ($variantId === null) {
			// A product with variants is bought through one of them, never as a whole.
			if ($this->variantRepository->hasActive($entity->id)) {
				$this->reject('Please choose a variant.');
				return;
			}
		} else {
			$variantEntity = $this->variantRepository->getOne($variantId) ?? $this->error('Variant not found.');
			if ($variantEntity->product_id !== $entity->id || !$variantEntity->active) {
				$this->error('Variant not found.');
			}

			$availableStock = $variantEntity->stock;
			$variantLabel = implode(', ', $this->variantRepository->getLabels($variantEntity->id));
		}

		$inCart = $this->shoppingCartSession->getAmount($entity->id, $variantId);
		if ($inCart + $amount > $availableStock) {
			$this->reject($inCart > 0
				? $this->translate('The product %s is only %d pcs in stock, %d of them already in your cart.', $entity->name, $availableStock, $inCart)
				: $this->translate('The product %s is only %d pcs in stock.', $entity->name, $availableStock));
			return;
		}

		$item = $this->priceResolver->forCart($entity, $variantEntity, $variantLabel, $amount);
		$this->shoppingCartSession->addItem($item, $amount, variantId: $variantId, variantLabel: $variantLabel);

		$this->getPresenter()->flashMessage('The product has been added to the cart.', Alert::Success);
		$this->getPresenter()->redrawControl('message');
		$this->getPresenter()->redrawControl('cart');
	}


	private function reject(string $message): void
	{
		$this->getPresenter()->flashMessage($message, Alert::Danger);
		$this->getPresenter()->redrawControl('message');
	}
}
