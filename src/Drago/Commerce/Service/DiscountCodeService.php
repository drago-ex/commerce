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
	 * Marks the currently applied discount code (if any) as used.
	 *
	 * Returns true when there was nothing to consume, or the usage was
	 * recorded successfully. Returns false only when a code was applied
	 * but a concurrent request has just exhausted its usage limit in the
	 * meantime — the caller should treat this as a failed checkout
	 * attempt (e.g. roll back the order) rather than silently ignoring it.
	 *
	 * @throws Exception
	 * @throws AttributeDetectionException
	 */
	public function consume(): bool
	{
		$discountCode = $this->getCode();
		if ($discountCode === null) {
			return true;
		}

		return $this->repository->incrementUsage($discountCode->id);
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
