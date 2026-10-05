<?php

declare(strict_types=1);

namespace Drago\Commerce\UI\ShoppingCart;

use Brick\Money\Exception\MoneyMismatchException;
use Dibi\Exception;
use Drago\Application\UI\Alert;
use Drago\Attr\AttributeDetectionException;
use Drago\Commerce\Domain\Product\ProductRepository;
use Drago\Commerce\Domain\Product\ProductVariantRepository;
use Drago\Commerce\Event\CartItemChanged;
use Drago\Commerce\Event\CartItemRemoved;
use Drago\Commerce\Event\EventDispatcher;
use Drago\Commerce\Service\DiscountCodeService;
use Drago\Commerce\Service\ShoppingCartSession;
use Drago\Commerce\UI\BaseControl;
use Drago\Commerce\UI\BaseForm;
use Drago\Commerce\UI\Factory;
use Drago\Commerce\UI\FactoryValues;
use Nette\Application\AbortException;
use Nette\Application\UI\Form;
use Nette\Application\UI\InvalidLinkException;
use Nette\Application\UI\Multiplier;


/**
 * @property-read SummaryCartTemplate $template
 */
class SummaryCartControl extends BaseControl
{
	public function __construct(
		private readonly ShoppingCartSession $shoppingCart,
		private readonly ProductRepository $productRepository,
		private readonly ProductVariantRepository $productVariantRepository,
		private readonly Factory $factory,
		private readonly EventDispatcher $eventDispatcher,
		private readonly DiscountCodeService $discountCodeService,
	) {
	}


	/**
	 * @throws MoneyMismatchException
	 * @throws InvalidLinkException
	 * @throws Exception
	 * @throws AttributeDetectionException
	 */
	public function render(): void
	{
		/** @var Multiplier<BaseForm> $multiplier */
		$multiplier = $this->getComponent('changeQuantity');
		foreach ($this->shoppingCart->getItems() as $item) {
			$form = $multiplier->getComponent(self::cartItemKey($item->product->id, $item->variantId));
			$form->setDefaults((array) $item);
		}

		$template = $this->template;
		$template->setFile($this->templateControl ?: __DIR__ . '/SummaryCart.latte');
		$template->setTranslator($this->translator);
		$template->originalPrice = $this->shoppingCart->getOriginalPrice();
		$template->subtotalPrice = $this->shoppingCart->getSubtotalPrice();
		$template->totalPrice = $this->shoppingCart->getTotalPrice();
		$template->productDiscountAmount = $template->originalPrice->minus($template->subtotalPrice);
		$template->discountAmount = $template->subtotalPrice->minus($template->totalPrice);
		$template->discountCode = $this->discountCodeService->getCode()?->code;
		$template->amountItems = $this->shoppingCart->getAmountItems();
		$template->shoppingCart = $this->shoppingCart->getItems();
		$template->linkOrderDelivery = $this->getPresenter()->link($this->linkRedirectTarget);
		$template->breadcrumbs = $this->getBreadcrumbs();
		$template->render();
	}


	/**
	 * Builds the composite key used for both the changeQuantity Multiplier
	 * and the quantity-change form's hidden fields, so two different
	 * variants of the same product each get their own independent form
	 * instead of colliding on a single "productId" key. Public so the
	 * template can build the identical key when accessing the component.
	 */
	public static function cartItemKey(int $productId, ?int $variantId): string
	{
		return $variantId !== null ? $productId . '_' . $variantId : (string) $productId;
	}


	/**
	 * Splits a composite cartItemKey() back into [productId, variantId].
	 *
	 * @return array{0: string, 1: ?int}
	 */
	private static function splitCartItemKey(string $key): array
	{
		$parts = explode('_', $key, 2);
		$productId = $parts[0];
		$variantId = isset($parts[1]) && $parts[1] !== '' ? (int) $parts[1] : null;
		return [$productId, $variantId];
	}


	/**
	 * Component for adding an item with amount to the cart.
	 *
	 * @return Multiplier<BaseForm>
	 */
	protected function createComponentChangeQuantity(): Multiplier
	{
		return new Multiplier(function (string $key) {
			[$productId, $variantId] = self::splitCartItemKey($key);

			$form = $this->factory->addChangeAmountInCart($productId, $variantId);
			$form->setTranslator($this->translator);
			$form->onSuccess[] = $this->changeQuantity(...);
			return $form;
		});
	}


	protected function createComponentDiscountCode(): BaseForm
	{
		$form = $this->factory->addDiscountCode();
		$form->setTranslator($this->translator);
		$form->onSuccess[] = $this->applyDiscountCode(...);
		return $form;
	}


	/**
	 * @throws AbortException
	 * @throws Exception
	 * @throws AttributeDetectionException
	 */
	public function applyDiscountCode(Form $form, FactoryValues $data): void
	{
		if (!$this->discountCodeService->apply($data->code)) {
			$form->addError('The discount code is invalid or expired.');
		}

		$this->redrawShoppingCart();
	}


	/**
	 * Redraw shopping cart on AJAX or redirect otherwise.
	 *
	 * @throws AbortException
	 */
	private function redrawShoppingCart(): void
	{
		if ($this->isAjax()) {
			$this->getPresenter()->redrawControl('shoppingCart');
			$this->getPresenter()->redrawControl('cart');
		} else {
			$this->redirect('this');
		}
	}


	/**
	 * Handles a quantity change of an item that is already in the cart.
	 * Lines that are not in the cart are ignored, so a crafted request cannot
	 * add a product or variant at a price it was never offered for.
	 *
	 * @throws AbortException
	 * @throws AttributeDetectionException
	 * @throws Exception
	 */
	public function changeQuantity(Form $form, FactoryValues $data): void
	{
		$productId = (int) $data->productId;
		$variantId = $data->variantId !== null && $data->variantId !== '' ? (int) $data->variantId : null;
		$amount = (int) $data->amount;

		$line = $this->shoppingCart->findItem($productId, $variantId);
		if ($line === null || $amount < 1) {
			$this->redrawShoppingCart();
			return;
		}

		$availableStock = $this->getAvailableStock($productId, $variantId);
		if ($availableStock === null) {
			$this->getPresenter()->flashMessage(
				$this->translate('The product %s is no longer available.', $line->product->name),
				Alert::Danger,
			);
			$this->getPresenter()->redrawControl('message');
			$this->redrawShoppingCart();
			return;
		}

		if ($availableStock < $amount) {
			$this->getPresenter()->flashMessage(
				$this->translate('The product %s is only %d pcs in stock.', $line->product->name, $availableStock),
				Alert::Danger,
			);
			$this->getPresenter()->redrawControl('message');
			$this->redrawShoppingCart();
			return;
		}

		$this->shoppingCart->addItem($line->product, $amount, dontCount: true, variantId: $variantId, variantLabel: $line->variantLabel);
		$this->eventDispatcher->dispatch(new CartItemChanged($line->product, $amount, $variantId, $line->variantLabel));
		$this->redrawShoppingCart();
	}


	/**
	 * Returns how many pieces can currently be bought, or null when the
	 * product or variant is gone, inactive, or needs a variant that is missing.
	 *
	 * @throws AttributeDetectionException
	 * @throws Exception
	 */
	private function getAvailableStock(int $productId, ?int $variantId): ?int
	{
		$product = $this->productRepository->getOne($productId);
		if ($product === null || !$product->active) {
			return null;
		}

		if ($variantId === null) {
			return $this->productVariantRepository->hasActive($productId) ? null : $product->stock;
		}

		$variant = $this->productVariantRepository->getOne($variantId);
		if ($variant === null || $variant->product_id !== $product->id || !$variant->active) {
			return null;
		}

		return $variant->stock;
	}


	/**
	 * Handles removing an item (a specific variant, if given) from the cart.
	 * Works on the cart line itself, so an item whose product has since been
	 * deleted or deactivated can still be removed.
	 *
	 * @throws AbortException
	 */
	public function handleRemoveItem(int $productId, ?int $variantId = null): void
	{
		$line = $this->shoppingCart->findItem($productId, $variantId);
		if ($line !== null) {
			$this->shoppingCart->removeLine($productId, $variantId);
			$this->eventDispatcher->dispatch(new CartItemRemoved($line->product, $variantId, $line->variantLabel));
		}

		$this->redrawShoppingCart();
	}


	/**
	 * Handles removing the applied discount code from the cart session.
	 *
	 * @throws AbortException
	 */
	public function handleRemoveDiscountCode(): void
	{
		$this->discountCodeService->remove();
		$this->redrawShoppingCart();
	}
}
