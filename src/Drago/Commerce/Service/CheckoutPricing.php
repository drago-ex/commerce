<?php

declare(strict_types=1);

namespace Drago\Commerce\Service;

use Brick\Money\Exception\MoneyMismatchException;
use Brick\Money\Exception\UnknownCurrencyException;
use Dibi\Exception;
use Drago\Attr\AttributeDetectionException;
use Drago\Commerce\Domain\Delivery\CarrierMapper;
use Drago\Commerce\Domain\Delivery\CarrierRepository;
use Drago\Commerce\Domain\Delivery\PaymentMapper;
use Drago\Commerce\Domain\Delivery\PaymentRepository;
use Drago\Commerce\Domain\Product\PriceResolver;
use Drago\Commerce\Domain\Product\ProductRepository;
use Drago\Commerce\Domain\Product\ProductVariantRepository;


/**
 * Brings prices kept in the session (cart lines, carrier, payment) up to date
 * with the database before an order is placed, so a customer never pays a price
 * that is no longer valid without having seen the change.
 */
class CheckoutPricing
{
	public function __construct(
		private readonly ShoppingCartSession $shoppingCartSession,
		private readonly OrderSession $orderSession,
		private readonly ProductRepository $productRepository,
		private readonly ProductVariantRepository $variantRepository,
		private readonly PriceResolver $priceResolver,
		private readonly CarrierRepository $carrierRepository,
		private readonly PaymentRepository $paymentRepository,
		private readonly CarrierMapper $carrierMapper,
		private readonly PaymentMapper $paymentMapper,
	) {
	}


	/**
	 * Reprices cart lines whose catalog price or discount changed since they were
	 * added. Lines of products that are gone or inactive are left for the stock
	 * reservation to report. Repricing dispatches ProductAddedToCart again for the
	 * changed lines, so listeners can apply their own pricing rules to the new price.
	 *
	 * @return list<string> Names of the products whose price changed.
	 * @throws Exception
	 * @throws AttributeDetectionException
	 * @throws UnknownCurrencyException
	 * @throws MoneyMismatchException
	 */
	public function refreshCart(): array
	{
		$changed = [];

		foreach ($this->shoppingCartSession->getItems() as $line) {
			$entity = $this->productRepository->getOne($line->product->id);
			if ($entity === null || !$entity->active) {
				continue;
			}

			$variant = null;
			if ($line->variantId !== null) {
				$variant = $this->variantRepository->getOne($line->variantId);
				if ($variant === null || $variant->product_id !== $entity->id || !$variant->active) {
					continue;
				}
			}

			$catalog = $this->priceResolver->catalog($entity, $variant);
			if ($line->product->catalog?->equals($catalog) === true) {
				continue;
			}

			$product = $this->priceResolver->forCart($entity, $variant, $line->variantLabel, $line->amount->toInt());
			$this->shoppingCartSession->replaceProduct($product, $line->variantId);

			if (!$product->getDiscountedPrice()->isEqualTo($line->product->getDiscountedPrice())) {
				$changed[] = $line->product->name;
			}
		}

		return $changed;
	}


	/**
	 * Reloads the selected carrier and payment method. A price change updates the
	 * selection; a removed one is dropped, so checkout asks for a new choice.
	 *
	 * @return bool True when the selection changed.
	 * @throws Exception
	 * @throws AttributeDetectionException
	 * @throws UnknownCurrencyException
	 * @throws MoneyMismatchException
	 */
	public function refreshDelivery(): bool
	{
		$changed = false;
		$order = $this->orderSession->getItems();

		if ($order->carrier !== null) {
			$entity = $this->carrierRepository->getOne($order->carrier->id);
			if ($entity === null) {
				$this->orderSession->removeCarrier();
				$changed = true;
			} else {
				$carrier = $this->carrierMapper->map($entity);
				if (!$carrier->price->isEqualTo($order->carrier->price)) {
					$this->orderSession->setCarrier($carrier);
					$changed = true;
				}
			}
		}

		if ($order->payment !== null) {
			$entity = $this->paymentRepository->getOne($order->payment->id);
			if ($entity === null) {
				$this->orderSession->removePayment();
				$changed = true;
			} else {
				$payment = $this->paymentMapper->map($entity);
				if (!$payment->price->isEqualTo($order->payment->price)) {
					$this->orderSession->setPayment($payment);
					$changed = true;
				}
			}
		}

		return $changed;
	}
}
