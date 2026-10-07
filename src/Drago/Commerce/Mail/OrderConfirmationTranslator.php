<?php

declare(strict_types=1);

namespace Drago\Commerce\Mail;

use Nette\Localization\Translator;
use Nette\Neon\Neon;
use function vsprintf;


final class OrderConfirmationTranslator implements Translator
{
	/** @var array<string, string> */
	private array $fallbackTranslations = [];


	public function __construct(
		private ?Translator $translator,
		string $lang,
	) {
		$catalog = match (strtolower($lang)) {
			'cs', 'cs-cz' => 'cs.neon',
			default => null,
		};
		if ($catalog === null) {
			return;
		}

		$translations = Neon::decodeFile(__DIR__ . '/../Translate/' . $catalog);
		if (!is_array($translations)) {
			return;
		}

		foreach ($translations as $key => $translation) {
			if (is_string($key) && is_string($translation)) {
				$this->fallbackTranslations[$key] = $translation;
			}
		}
	}


	public function translate(string|\Stringable $message, mixed ...$parameters): string
	{
		$key = (string) $message;
		$translation = $this->translator?->translate($key);
		if ($translation !== null && (string) $translation !== $key) {
			return (string) ($parameters === []
				? $translation
				: $this->translator->translate($key, ...$parameters));
		}

		$translation = $this->fallbackTranslations[$key] ?? $key;
		return $parameters !== [] && str_contains($translation, '%')
			? vsprintf($translation, $parameters)
			: $translation;
	}
}
