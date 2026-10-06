<?php

declare(strict_types=1);

namespace Drago\Commerce\UI;

use Brick\Money\Exception\MoneyMismatchException;
use Brick\Money\Money;
use Dibi\Exception;
use Drago\Application\UI\ExtraControl;
use Drago\Attr\AttributeDetectionException;
use Drago\Commerce\Domain\DiscountCode\DiscountCodeEntity;
use Drago\Commerce\Service\DiscountCodeService;
use Drago\Commerce\Service\OrderSession;
use Drago\Commerce\Service\ShoppingCartSession;
use function vsprintf;


/**
 * Manages checkout steps and their completion state for breadcrumb rendering.
 */
class BaseControl extends ExtraControl
{
	/**
	 * Optional custom template file for rendering the control.
	 */
	public ?string $templateControl = null;

	/**
	 * Redirect to the next step of the order.
	 */
	public string $linkRedirectTarget;

	/**
	 * Checkout step labels keyed by action identifier.
	 *
	 * @var array<string, string>
	 */
	protected array $steps = [];

	protected string $currentStep = '';

	/** @var list<string> */
	protected array $completedSteps = [];


	/** @param array<string, string> $steps */
	public function setSteps(array $steps): void
	{
		$this->steps = $steps;
	}


	public function setCurrentStep(string $currentStep): void
	{
		$this->currentStep = $currentStep;
	}


	/** @param list<string> $completedSteps */
	public function setCompletedSteps(array $completedSteps): void
	{
		$this->completedSteps = $completedSteps;
	}


	public function getBreadcrumbs(): Breadcrumbs
	{
		return new Breadcrumbs(
			steps: $this->steps,
			completedSteps: $this->completedSteps,
			currentStep: $this->currentStep,
		);
	}


	/**
	 * Populates the shared cart totals used by checkout templates.
	 *
	 * @throws AttributeDetectionException
	 * @throws Exception
	 * @throws MoneyMismatchException
	 */
	protected function prepareShoppingCartSummary(
		BaseTemplate $template,
		ShoppingCartSession $shoppingCart,
		DiscountCodeService $discountCodeService,
		?OrderSession $orderSession = null,
	): void
	{
		$discountCode = $discountCodeService->getCode();
		$template->shoppingCart = $shoppingCart->getItems();
		$template->amountItems = $shoppingCart->getAmountItems();
		$template->originalPrice = $shoppingCart->getOriginalPrice();
		$template->subtotalPrice = $shoppingCart->getSubtotalPrice();
		$template->productDiscountAmount = $template->originalPrice->minus($template->subtotalPrice);
		$template->discountCode = $discountCode?->code;

		$cartTotalPrice = $shoppingCart->getTotalPrice($discountCode);
		$template->discountAmount = $template->subtotalPrice->minus($cartTotalPrice);
		$template->totalPrice = $this->calculateTotalPrice(
			$shoppingCart,
			$discountCodeService,
			$orderSession,
			$discountCode,
		);
	}


	/**
	 * Calculates the cart total, including delivery and payment when present.
	 *
	 * @throws AttributeDetectionException
	 * @throws Exception
	 * @throws MoneyMismatchException
	 */
	protected function calculateTotalPrice(
		ShoppingCartSession $shoppingCart,
		DiscountCodeService $discountCodeService,
		?OrderSession $orderSession = null,
		?DiscountCodeEntity $discountCode = null,
	): Money
	{
		$totalPrice = $shoppingCart->getTotalPrice($discountCode ?? $discountCodeService->getCode());
		if ($orderSession === null) {
			return $totalPrice;
		}

		return $totalPrice->plus($orderSession->getCarrierPrice())->plus($orderSession->getPaymentPrice());
	}


	/**
	 * Translates a message with sprintf-style parameters. Without a translator,
	 * the message is returned with the parameters filled in.
	 */
	protected function translate(string $message, string|int ...$params): string
	{
		if ($this->translator !== null) {
			return (string) $this->translator->translate($message, ...$params);
		}

		return $params === [] ? $message : vsprintf($message, $params);
	}


	/**
	 * Sets the action used by the control's navigation link.
	 *
	 * @throws \InvalidArgumentException The target action is empty.
	 */
	public function setLinkRedirectTarget(string $link): void
	{
		if (empty($link)) {
			throw new \InvalidArgumentException('Redirect target link cannot be empty.');
		}
		$this->linkRedirectTarget = $link;
	}
}
