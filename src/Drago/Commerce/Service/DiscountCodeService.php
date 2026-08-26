<?php

declare(strict_types=1);

namespace Drago\Commerce\Service;

use Brick\Math\RoundingMode;
use Brick\Money\Exception\MoneyMismatchException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Dibi\Exception;
use Drago\Attr\AttributeDetectionException;
use Drago\Commerce\Domain\DiscountCode\DiscountCodeEntity;
use Drago\Commerce\Domain\DiscountCode\DiscountCodeRepository;
use Nette\Http\Session;
use Nette\Http\SessionSection;


class DiscountCodeService
{
	private const string Code = 'code';

	private SessionSection $sessionSection;


	public function __construct(
		Session $session,
		private readonly DiscountCodeRepository $repository,
	) {
		$this->sessionSection = $session
			->getSection(self::class)
			->setExpiration('1 day');
	}


	/**
	 * Applies a valid discount code to the current cart session.
	 *
	 * @throws Exception
	 * @throws AttributeDetectionException
	 */
	public function apply(string $code): bool
	{
		$discountCode = $this->repository->findValid($code);
		if ($discountCode === null) {
			return false;
		}

		$this->sessionSection->set(self::Code, $discountCode->code);
		return true;
	}


	public function remove(): void
	{
		$this->sessionSection->remove(self::Code);
	}


	/**
	 * @throws Exception
	 * @throws AttributeDetectionException
	 */
	public function consume(): void
	{
		$discountCode = $this->getCode();
		if ($discountCode !== null) {
			$this->repository->incrementUsage($discountCode->id);
		}
	}


	/**
	 * @throws Exception
	 * @throws AttributeDetectionException
	 */
	public function getCode(): ?DiscountCodeEntity
	{
		$code = $this->sessionSection->get(self::Code);
		if (!is_string($code)) {
			return null;
		}

		$discountCode = $this->repository->findValid($code);
		if ($discountCode === null) {
			$this->remove();
		}

		return $discountCode;
	}


	/**
	 * @throws MoneyMismatchException
	 * @throws AttributeDetectionException
	 * @throws UnknownCurrencyException
	 * @throws Exception
	 */
	public function applyTo(Money $total, ?DiscountCodeEntity $discountCode = null): Money
	{
		$discountCode ??= $this->getCode();
		if ($discountCode === null) {
			return $total;
		}

		if (
			$discountCode->minimum_order_amount !== null
			&& $total->isLessThan(Money::of($discountCode->minimum_order_amount, $total->getCurrency()))
		) {
			return $total;
		}

		if ($discountCode->type === 'percent') {
			$discount = $total
				->multipliedBy($discountCode->value / 100, RoundingMode::HALF_UP);
		} else {
			$discount = Money::of($discountCode->value, $total->getCurrency());
		}

		$discount = $discount->isGreaterThan($total) ? $total : $discount;
		return $total->minus($discount);
	}
}
