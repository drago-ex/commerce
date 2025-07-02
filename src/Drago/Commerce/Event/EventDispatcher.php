<?php

declare(strict_types=1);

namespace Drago\Commerce\Event;


/**
 * Simple event dispatcher.
 * Registers listeners and dispatches events.
 */
class EventDispatcher
{
	/** @var array<string, callable|object[]> Listeners by event class */
	private array $listeners = [];


	/**
	 * Add listener for event class.
	 */
	public function addListener(string $eventClass, callable|object $listener): void
	{
		$this->listeners[$eventClass][] = $listener;
	}


	/**
	 * Dispatch event to listeners.
	 */
	public function dispatch(object $event): void
	{
		$eventClass = $event::class;
		foreach ($this->listeners[$eventClass] ?? [] as $listener) {
			$listener($event);
		}
	}
}
