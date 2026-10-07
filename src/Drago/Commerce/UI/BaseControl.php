<?php

declare(strict_types=1);

namespace Drago\Commerce\UI;

use Brick\Money\Exception\MoneyMismatchException;
use Brick\Money\Money;
use Drago\Application\UI\ExtraControl;
use Drago\Commerce\Service\CartTotals;
use Nette\Application\UI\Form;


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
	 * Sets the template file (a custom one wins) and the translator.
	 */
	protected function prepareTemplate(string $defaultFile): void
	{
		$this->template->setFile($this->templateControl ?: $defaultFile);
		$this->template->setTranslator($this->translator);
	}


	/**
	 * Fills the cart summary shown by every checkout step. The extra prices
	 * (delivery, payment) are added to the total.
	 *
	 * @throws MoneyMismatchException
	 */
	protected function applyCartTotals(BaseTemplate $template, CartTotals $cart, Money ...$extras): void
	{
		$template->shoppingCart = $cart->items;
		$template->amountItems = $cart->amountItems;
		$template->originalPrice = $cart->originalPrice;
		$template->subtotalPrice = $cart->subtotalPrice;
		$template->productDiscountAmount = $cart->productDiscountAmount;
		$template->discountAmount = $cart->discountAmount;
		$template->discountCode = $cart->discountCode?->code;
		$template->totalPrice = $cart->withExtras(...$extras);
	}


	/**
	 * Pre-fills a step form with the values kept in the session and relabels its
	 * button, when the customer returns to the step.
	 *
	 * @param array<string, mixed>|object $defaults
	 */
	protected function prefillForm(string $component, array|object $defaults): void
	{
		$form = $this->getComponent($component);
		if (!$form instanceof Form || $form->isSubmitted()) {
			return;
		}

		$this->getFormComponent($form, 'send')?->setCaption('Update');
		$form->setDefaults($defaults);
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
