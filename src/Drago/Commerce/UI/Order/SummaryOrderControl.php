<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Commerce\UI\Order;

use Brick\Money\Exception\MoneyMismatchException;
use Brick\Money\Money;
use Brick\PhoneNumber\PhoneNumberFormat;
use DateTimeImmutable;
use Dibi\DriverException;
use Dibi\Exception;
use Drago\Attr\AttributeDetectionException;
use Drago\Commerce\Domain\Customer\Customer;
use Drago\Commerce\Domain\Customer\CustomerRepository;
use Drago\Commerce\Domain\Order\OrderProductRepository;
use Drago\Commerce\Domain\Order\OrderRepository;
use Drago\Commerce\Domain\Product\ProductEntity;
use Drago\Commerce\Domain\Product\ProductRepository;
use Drago\Commerce\Service\OrderSession;
use Drago\Commerce\Service\ShoppingCartSession;
use Drago\Commerce\UI\BaseControl;
use Nette\Application\UI\Form;
use Tracy\Debugger;


/**
 * @property-read SummaryOrderTemplate $template
 */
class SummaryOrderControl extends BaseControl
{
	public function __construct(
		private readonly ShoppingCartSession $shoppingCartSession,
		private readonly OrderSession $orderSession,
		private readonly OrderRepository $orderRepository,
		private readonly OrderProductRepository $orderProductsRepository,
		private readonly CustomerRepository $customerRepository,
		private readonly ProductRepository $productRepository,
	) {
	}


	/**
	 * @throws MoneyMismatchException
	 */
	public function render(): void
	{
		$template = $this->template;
		$template->setFile($this->templateControl ?: __DIR__ . '/Summary.latte');
		$template->shoppingCart = $this->shoppingCartSession->getItems();
		$template->amountItems = $this->shoppingCartSession->getAmountItems();
		$template->totalPrice = $this->getTotalPrice();
		$template->carrier = $this->getOrderItem('carrier');
		$template->customer = $this->getOrderItem('customer');
		$template->payment = $this->getOrderItem('payment');
		$template->breadcrumbs = $this->getBreadcrumbs();
		$template->render();
	}


	private function getOrderItem(string $name): mixed
	{
		$items = $this->orderSession->getItems();
		return $items->{$name} ?? null;
	}


	/**
	 * @throws MoneyMismatchException
	 */
	private function getTotalPrice(): Money
	{
		return $this->shoppingCartSession->getTotalPrice()
			->plus($this->orderSession->getCarrierPrice())
			->plus($this->orderSession->getPaymentPrice());
	}


	private function getAmountPrice(Money $money): float
	{
		$money->isZero();
		return $money->getAmount()
			->toFloat();
	}


	protected function createComponentSendOrder(): Form
	{
		$form = new Form;
		$form->addSubmit('send', 'Confirm the purchase');
		$form->onSuccess[] = $this->processOrder(...);
		return $form;
	}


	/**
	 * @throws MoneyMismatchException
	 * @throws DriverException
	 * @throws \Exception
	 */
	public function processOrder(Form $form): void
	{
		$order = $this->orderSession->getItems();
		$customer = $order->customer;

		try {
			$this->orderRepository->getConnection()->begin();

			// Save the customer.
			$customerData = new Customer(
				email: $customer->email,
				phone: $customer->phone->format(PhoneNumberFormat::INTERNATIONAL),
				name: $customer->name,
				surname: $customer->surname,
				street: $customer->street,
				city: $customer->city,
				post_code: $customer->post_code,
				country: $customer->country,
				note: $customer->note,
			);
			$this->customerRepository->save((array) $customerData);

			// Save order.
			$orderData = new Order(
				customer_id: $this->customerRepository->getInsertId(),
				carrier_id: $order->carrier->id,
				payment_id: $order->payment->id,
				carrier_price: $this->getAmountPrice($order->carrier->price),
				payment_price: $this->getAmountPrice($order->payment->price),
				total_price: $this->getAmountPrice($this->getTotalPrice()),
				created_at: new DateTimeImmutable,
			);

			$this->orderRepository->save((array) $orderData);

			$orderId = $this->orderRepository->getInsertId();
			foreach ($this->shoppingCartSession->getItems() as $item) {
				$product = $this->productRepository->getOne($item->product->id);

				if ($product->stock < $item->amount->toInt()) {
					throw new \Exception("The product '{$product->name}' is not in stock in the requested quantity.");
				}

				// Deduct inventory.
				$newStock = $product->stock - $item->amount->toInt();
				$productEntity = new ProductEntity;
				$productEntity->id = $product->id;
				$productEntity->stock = $newStock;
				$this->productRepository->save($productEntity);

				//Save order products.
				$orderProduct = new OrderProduct(
					order_id: $orderId,
					product_id: $item->product->id,
					amount: $item->amount->toInt(),
				);
				$this->orderProductsRepository->save((array) $orderProduct);
			}

			$this->orderRepository->getConnection()->commit();

			$this->shoppingCartSession->remove();
			$this->orderSession->remove();
			$this->getPresenter()->redirect($this->linkRedirectTarget);

		} catch (Exception | AttributeDetectionException $e) {
			$this->orderRepository->getConnection()->rollback();
			Debugger::barDump($e);
		}
	}
}
