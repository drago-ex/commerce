<?php declare(strict_types=1);

namespace Drago\Commerce\Service;

use Brick\Math\RoundingMode;
use Brick\Money\Exception\MoneyMismatchException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Dibi\Exception;
use Drago\Attr\AttributeDetectionException;
use Drago\Commerce\Commerce;
use Drago\Commerce\Domain\DiscountCode\DiscountCodeEntity;
use Drago\Commerce\Domain\DiscountCode\DiscountCodeRepository;
use Nette\Http\Session;
use Nette\Http\SessionSection;
use function is_string;


class DiscountCodeService
{
	private const string Code = 'code';

	private SessionSection $sessionSection;

	/**
	 * The code lookup made during this request, so rendering several controls
	 * does not query the database again for the same code.
	 *
	 * @var array{string, ?DiscountCodeEntity}|null
	 */
	private ?array $lookup = null;


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
		$this->lookup = null;
		$discountCode = $this->repository->findValid($code);
		if ($discountCode === null || $discountCode->value <= 0) {
			return false;
		}

		$this->sessionSection->set(self::Code, $discountCode->code);
		return true;
	}


	public function remove(): void
	{
		$this->lookup = null;
		$this->sessionSection->remove(self::Code);
	}


	/**
	 * Marks a discount code as used. Without an argument it uses the code
	 * currently applied to the session (if any); pass the code the order was
	 * priced with so the usage is recorded for exactly that code.
	 *
	 * Returns true when there was nothing to consume, or the usage was
	 * recorded successfully. Returns false when the code can no longer be
	 * used (limit reached, deactivated or expired in the meantime) — the
	 * caller should treat this as a failed checkout attempt (e.g. roll back
	 * the order) rather than silently ignoring it.
	 *
	 * @throws Exception
	 * @throws AttributeDetectionException
	 */
	public function consume(?DiscountCodeEntity $discountCode = null): bool
	{
		$discountCode ??= $this->getCode();
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

		if ($this->lookup === null || $this->lookup[0] !== $code) {
			$this->lookup = [$code, $this->repository->findValid($code)];
		}

		$discountCode = $this->lookup[1];
		if ($discountCode === null || $discountCode->value <= 0) {
			$this->remove();
			return null;
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

		if ($discountCode->value <= 0) {
			return $total;
		}

		if (
			$discountCode->minimumOrderAmount !== null
			&& $total->isLessThan(Commerce::money($discountCode->minimumOrderAmount, $total->getCurrency()->getCurrencyCode()))
		) {
			return $total;
		}

		if ($discountCode->type === 'percent') {
			// total × value / 100 exactly, rounded half up only at the end (12.5 % of 33.33 is 4.16625)
			$discount = $total->toRational()
				->multipliedBy(Commerce::decimal($discountCode->value))
				->dividedBy(100)
				->toContext($total->getContext(), RoundingMode::HalfUp);
		} else {
			$discount = Commerce::money($discountCode->value, $total->getCurrency()->getCurrencyCode());
		}

		$discount = $discount->isGreaterThan($total) ? $total : $discount;
		return $total->minus($discount);
	}
}
