<?php

declare(strict_types=1);

namespace Drago\Commerce\UI\ShoppingCart;

use Brick\Money\Exception\MoneyMismatchException;
use Drago\Commerce\Service\ShoppingCartSession;
use Drago\Commerce\UI\BaseControl;
use Nette\Application\UI\InvalidLinkException;


/** @property-read MiniCartTemplate $template */
class MiniCartControl extends BaseControl
{
	public function __construct(
		private readonly ShoppingCartSession $shoppingCartSession,
	) {
	}


	/**
	 * @throws MoneyMismatchException
	 * @throws InvalidLinkException
	 */
	public function render(): void
	{
		$this->prepareTemplate(__DIR__ . '/MiniCart.latte');
		$template = $this->template;
		$template->amountItems = $this->shoppingCartSession->getAmountItems();
		$template->linkShoppingCart = $this->getPresenter()->link($this->linkRedirectTarget);
		$template->formattedTotalPrice = $template->money($this->shoppingCartSession->getTotalPrice());
		$template->render();
	}
}
