<?php

declare(strict_types=1);

namespace Drago\Commerce\UI\ShoppingCart;

use Brick\Money\Exception\MoneyMismatchException;
use Brick\Money\Exception\UnknownCurrencyException;
use Dibi\Exception;
use Drago\Application\UI\Alert;
use Drago\Attr\AttributeDetectionException;
use Drago\Commerce\Domain\Product\ProductMapper;
use Drago\Commerce\Domain\Product\ProductRepository;
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
use Nette\Application\BadRequestException;
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
		private readonly ProductMapper $productMapper,
		private readonly Factory $factory,
		private readonly EventDispatcher $eventDispatcher,
		private readonly DiscountCodeService $discountCodeService,
	) {
	}


	/**
	 * @throws MoneyMismatchException
	 * @throws InvalidLinkException
	 * @throws Exception
	 */
	public function render(): void
	{
		// Set default values for addToBasket forms based on basket items
		/** @var Multiplier<BaseForm> $multiplier */
		$multiplier = $this->getComponent('changeQuantity');
		foreach ($this->shoppingCart->getItems() as $item) {
			$form = $multiplier->getComponent((string) $item->product->id);
			$form->setDefaults((array) $item);
		}

		$template = $this->template;
		$template->setFile($this->templateControl ?: __DIR__ . '/SummaryCart.latte');
		$template->setTranslator($this->translator);
		$template->totalPrice = $this->shoppingCart->getTotalPrice();
		$template->discountCode = $this->discountCodeService->getCode()?->code;
		$template->amountItems = $this->shoppingCart->getAmountItems();
		$template->shoppingCart = $this->shoppingCart->getItems();
		$template->linkOrderDelivery = $this->getPresenter()->link($this->linkRedirectTarget);
		$template->breadcrumbs = $this->getBreadcrumbs();
		$template->render();
	}


	/**
	 * Component for adding an item with amount to the cart.
	 *
	 * @return Multiplier<BaseForm>
	 */
	protected function createComponentChangeQuantity(): Multiplier
	{
		return new Multiplier(function (string $productId) {
			$form = $this->factory->addChangeAmountInCart($productId);
			$form->setTranslator($this->translator);
			$form->onSuccess[] = $this->changeQuantity(...);
			return $form;
		});
	}


	protected function createComponentDiscountCode(): BaseForm
	{
		$form = $this->factory->addDiscountCode();
		$form->onSuccess[] = $this->applyDiscountCode(...);
		return $form;
	}


	/**
	 * @throws AbortException
	 * @throws Exception
	 */
	public function applyDiscountCode(Form $form, FactoryValues $data): void
	{
		if (!$this->discountCodeService->apply($data->code)) {
			$this->getPresenter()->flashMessage('The discount code is invalid or expired.', Alert::Danger);
			$this->getPresenter()->redrawControl('message');
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
	 * Handles successful add to basket form submission.
	 *
	 * @throws AbortException
	 * @throws AttributeDetectionException
	 * @throws Exception
	 * @throws UnknownCurrencyException
	 * @throws BadRequestException
	 */
	public function changeQuantity(Form $form, FactoryValues $data): void
	{
		$productEntity = $this->productRepository->getOne($data->productId) ?? $this->error('Product not found');
		$product = $this->productMapper->map($productEntity);

		if ($productEntity->stock < $data->amount) {
			$message = "The product $product->name is only $productEntity->stock pcs in stock.";
			$this->getPresenter()->flashMessage($message, Alert::Danger);
			$this->getPresenter()->redrawControl('message');
			$this->redrawShoppingCart();
			return;
		}

		$this->shoppingCart->addItem($product, $data->amount, dontCount: true);
		$this->eventDispatcher->dispatch(new CartItemChanged($product, $data->amount));
		$this->redrawShoppingCart();
	}


	/**
	 * Handles removing an item from the cart.
	 *
	 * @throws AbortException
	 * @throws AttributeDetectionException
	 * @throws Exception
	 * @throws UnknownCurrencyException
	 * @throws BadRequestException
	 */
	public function handleRemoveItem(int $productId): void
	{
		$productEntity = $this->productRepository->getOne($productId) ?? $this->error('Product not found');
		$product = $this->productMapper->map($productEntity);
		$this->shoppingCart->removeItem($product);

		$this->eventDispatcher->dispatch(new CartItemRemoved($product));
		$this->redrawShoppingCart();
	}
}
