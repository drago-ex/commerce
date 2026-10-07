<?php

declare(strict_types=1);

namespace Drago\Commerce\UI\Product;

use Brick\Money\Exception\MoneyMismatchException;
use Brick\Money\Exception\UnknownCurrencyException;
use Dibi\Exception;
use Drago\Application\UI\Alert;
use Drago\Attr\AttributeDetectionException;
use Drago\Commerce\Commerce;
use Drago\Commerce\Domain\Product\PriceResolver;
use Drago\Commerce\Domain\Product\ProductEntity;
use Drago\Commerce\Domain\Product\ProductRepository;
use Drago\Commerce\Domain\Product\ProductVariantRepository;
use Drago\Commerce\Service\ShoppingCartSession;
use Drago\Commerce\UI\BaseControl;
use Drago\Commerce\UI\BaseForm;
use Drago\Commerce\UI\Factory;
use Nette\Application\Attributes\Persistent;
use Nette\Application\UI\Form;
use Nette\Application\UI\Multiplier;
use Nette\Utils\Paginator;


/**
 * @property-read ProductTemplate $template
 */
class ProductControl extends BaseControl
{
	/** Current page of the listing. */
	#[Persistent]
	public int $page = 1;


	public function __construct(
		private readonly ProductRepository $productRepository,
		private readonly ProductVariantRepository $variantRepository,
		private readonly ShoppingCartSession $shoppingCartSession,
		private readonly Commerce $commerce,
		private readonly Factory $factory,
		private readonly PriceResolver $priceResolver,
	) {
	}


	/**
	 * @throws Exception
	 * @throws AttributeDetectionException
	 * @throws UnknownCurrencyException
	 */
	public function render(): void
	{
		$this->prepareTemplate(__DIR__ . '/Product.latte');
		$template = $this->template;

		$perPage = $this->commerce->getItemsPerPage();
		if ($perPage > 0) {
			$paginator = new Paginator;
			$paginator->setItemsPerPage($perPage);
			$paginator->setItemCount($this->productRepository->countActive());
			$paginator->setPage($this->page);
			$template->paginator = $paginator;
			$template->products = $this->productRepository->getPage($paginator->getLength(), $paginator->getOffset());
		} else {
			$template->paginator = null;
			$template->products = $this->productRepository->getAll();
		}

		$template->variantSummaries = $this->variantRepository->getSummaries(
			array_values(array_map(static fn(ProductEntity $product): int => $product->id, $template->products)),
		);

		$template->lowestPrices = [];
		foreach ($template->products as $product) {
			$summary = $template->variantSummaries[$product->id] ?? null;
			if ($summary !== null) {
				$template->lowestPrices[$product->id] = $summary->getLowestPrice($product, $this->commerce);
			}
		}

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
			$form = $this->factory->addHiddenProductId($productId, translator: $this->translator);
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

		$inCart = $this->shoppingCartSession->getAmount($entity->id);
		if ($inCart + 1 > $entity->stock) {
			$this->addFlashMessage(
				$this->translate('The product %s is only %d pcs in stock, %d of them already in your cart.', $entity->name, $entity->stock, $inCart),
				Alert::Danger,
			);
			$this->finish();
			return;
		}

		$this->shoppingCartSession->addItem($this->priceResolver->forCart($entity, null, null, 1));
		$this->addFlashMessage('The product has been added to the cart.', Alert::Success);
		$this->finish();
	}


	private function finish(): void
	{
		if ($this->isAjax()) {
			$this->addRedraw('message');
			$this->addRedraw('cart');
		} else {
			$this->addRedirect('this');
		}
	}


	/**
	 * Validates product existence, activity, and stock, redirecting with a
	 * flash message when the product cannot be purchased directly. A product
	 * with variants has to be bought through one of them on its detail page.
	 *
	 * @throws Exception
	 * @throws AttributeDetectionException
	 */
	private function validateProduct(?ProductEntity $product): void
	{
		if (!$product || !$product->active) {
			$this->addFlashMessage('The product does not exist or is not active.', Alert::Danger);
			$this->addRedirect('this');
		}

		if ($this->variantRepository->hasActive($product->id)) {
			$this->addFlashMessage('Please choose a variant.', Alert::Warning);
			$this->addRedirect('this');
		}

		if ($product->stock <= 0) {
			$this->addFlashMessage('The product is out of stock.', Alert::Warning);
			$this->addRedirect('this');
		}
	}
}
