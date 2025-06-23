<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Commerce\UI\ShoppingCart;

use Brick\Money\Exception\MoneyMismatchException;
use Drago\Application\UI\ExtraControl;
use Drago\Commerce\Service\ShoppingCartSession;
use Nette\Application\UI\InvalidLinkException;


/**
 * Basket control shows a summary of items in the shopping cart.
 *
 * @property-read MiniCartTemplate $template
 */
class MiniCartControl extends ExtraControl
{
	/** URL or presenter link target for the shopping cart */
	private string $linkRedirectTarget;


	public function __construct(
		private readonly ShoppingCartSession $shoppingCartSession,
	) {
	}


	public function setLinkRedirectTarget(string $link): void
	{
		if (empty($link)) {
			throw new \InvalidArgumentException('Redirect target link cannot be empty.');
		}
		$this->linkRedirectTarget = $link;
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
		$template->setFile(__DIR__ . '/MiniCart.latte');
		$template->setTranslator($this->translator);
		$template->totalPrice = $this->shoppingCartSession->getTotalPrice();
		$template->amountItems = $this->shoppingCartSession->getAmountItems();
		$template->linkShoppingCart = $this->getPresenter()->link($this->linkRedirectTarget);
		$template->render();
	}
}
