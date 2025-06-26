<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Commerce\UI\ShoppingCart;

use Brick\Money\Exception\MoneyMismatchException;
use Drago\Commerce\Service\ShoppingCartSession;
use Drago\Commerce\UI\BaseControl;
use Nette\Application\UI\InvalidLinkException;


/**
 * Basket control shows a summary of items in the shopping cart.
 *
 * @property-read MiniCartTemplate $template
 */
class MiniCartControl extends BaseControl
{
	public function __construct(
		private readonly ShoppingCartSession $shoppingCartSession,
	) {
	}


	/**
	 * Render the basket summary.
	 *
	 * @throws MoneyMismatchException
	 * @throws InvalidLinkException
	 */
	public function render(): void
	{
		$template = $this->template;
		$template->setFile($this->templateControl ?: __DIR__ . '/MiniCart.latte');
		$template->setTranslator($this->translator);
		$template->amountItems = $this->shoppingCartSession->getAmountItems();
		$template->linkShoppingCart = $this->getPresenter()->link($this->linkRedirectTarget);
		$template->formattedTotalPrice = $template->money($this->shoppingCartSession->getTotalPrice());
		$template->render();
	}
}
