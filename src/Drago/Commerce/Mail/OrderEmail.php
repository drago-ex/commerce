<?php

declare(strict_types=1);

namespace Drago\Commerce\Mail;

use Nette\Bridges\ApplicationLatte\TemplateFactory;
use Nette\Localization\Translator;
use Nette\Mail\Mailer;
use Nette\Mail\Message;
use Throwable;


/**
 * Renders and sends the order confirmation email.
 */
readonly class OrderEmail
{
	public function __construct(
		private Mailer $mailer,
		private TemplateFactory $templateFactory,
		private string $sender,
		private ?string $templateFile = null,
		private string $storeName = '',
		private string $storeEmail = '',
		private ?Translator $translator = null,
	) {
	}


	/**
	 * @param string|null $lang Passed to the translator's setTranslate() when supported; null keeps its current language.
	 * @throws Throwable
	 */
	public function send(OrderEmailData $order, ?string $lang = null): void
	{
		if ($lang !== null && $this->translator !== null && method_exists($this->translator, 'setTranslate')) {
			$this->translator->setTranslate($lang);
		}

		$template = $this->templateFactory->createTemplate(class: OrderEmailTemplate::class);
		$template->setFile($this->templateFile ?? __DIR__ . '/order-email.latte');
		$template->setTranslator($this->translator);
		$template->order = $order;
		$template->lang = $lang;
		$template->storeName = $this->storeName;
		$template->storeEmail = $this->storeEmail;

		$subject = $this->translator?->translate('Order confirmation #%d', $order->orderId)
			?? sprintf('Order confirmation #%d', $order->orderId);

		$message = new Message;
		$message->setFrom($this->sender)
			->addTo($order->customer->email, trim($order->customer->name . ' ' . $order->customer->surname))
			->setSubject((string) $subject)
			->setHtmlBody($template->renderToString());

		$this->mailer->send($message);
	}
}
