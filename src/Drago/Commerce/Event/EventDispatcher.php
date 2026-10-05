<?php

declare(strict_types=1);

namespace Drago\Commerce\Event;


/**
 * Calls the listeners registered for an event class, in the order they were added,
 * synchronously within the current request. An exception in a listener propagates
 * to the code that dispatched the event.
 */
class EventDispatcher
{
	/** @var array<string, list<callable>> Listeners by event class */
	private array $listeners = [];


	/**
	 * @param class-string $eventClass
	 */
	public function addListener(string $eventClass, callable $listener): void
	{
		$this->listeners[$eventClass][] = $listener;
	}


	public function dispatch(object $event): void
	{
		foreach ($this->listeners[$event::class] ?? [] as $listener) {
			$listener($event);
		}
	}
}
