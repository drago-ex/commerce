<?php

declare(strict_types=1);

namespace Drago\Commerce\Event;


class EventDispatcher
{
	/** @var array<string, list<callable|object>> Listeners by event class */
	private array $listeners = [];


	public function addListener(string $eventClass, callable|object $listener): void
	{
		$this->listeners[$eventClass][] = $listener;
	}


	public function dispatch(object $event): void
	{
		$eventClass = $event::class;
		foreach ($this->listeners[$eventClass] ?? [] as $listener) {
			if (is_callable($listener)) {
				$listener($event);
			}
		}
	}
}
