<?php

declare(strict_types=1);

namespace Drago\Commerce\Mail;

use Nette\Bridges\ApplicationLatte\TemplateFactory;
use Nette\Localization\Translator;
use Nette\Mail\Mailer;
use Nette\Mail\Message;
use Throwable;
use Tracy\Debugger;


readonly class OrderConfirmationMailer
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


	public function send(OrderConfirmation $order, string $lang = 'en'): void
	{
		try {
			$translator = $this->translator;
			if ($translator !== null && method_exists($translator, 'setTranslate')) {
				$translator->setTranslate($lang);
			}

			$template = $this->templateFactory->createTemplate(class: OrderConfirmationTemplate::class);
			$template->setFile($this->templateFile ?? __DIR__ . '/order-confirmation.latte');
			$template->setTranslator($translator);
			$template->order = $order;
			$template->lang = $lang;
			$template->storeName = $this->storeName;
			$template->storeEmail = $this->storeEmail;

			$message = new Message;
			$message->setFrom($this->sender)
				->addTo($order->customer->email)
				->setSubject((string) ($translator?->translate('Order confirmation #%d', $order->orderId) ?? sprintf('Order confirmation #%d', $order->orderId)))
				->setHtmlBody($template->renderToString());

			$this->mailer->send($message);
		} catch (Throwable $e) {
			Debugger::log($e, 'commerce-order-email');
		}
	}
}
