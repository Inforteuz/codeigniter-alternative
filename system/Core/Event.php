<?php

namespace System\Core;

/**
 * Event System - v1.0.0
 * 
 * Simple, decoupled event management for the framework.
 * Allows components to communicate without direct dependencies.
 */
class Event
{
    /**
     * @var array Registered listeners for events
     */
    protected static array $listeners = [];

    /**
     * Register a listener for an event.
     * 
     * @param string $eventName
     * @param callable $callback
     * @param int $priority Higher values run sooner
     * @return void
     */
    public static function listen(string $eventName, callable $callback, int $priority = 100): void
    {
        if (!isset(self::$listeners[$eventName])) {
            self::$listeners[$eventName] = [];
        }

        self::$listeners[$eventName][] = [
            'callback' => $callback,
            'priority' => $priority
        ];

        // Sort by priority
        usort(self::$listeners[$eventName], fn($a, $b) => $b['priority'] <=> $a['priority']);
    }

    /**
     * Dispatch an event to all registered listeners.
     * 
     * @param string $eventName
     * @param mixed $payload Data to pass to listeners
     * @return array Array of results from listeners
     */
    public static function dispatch(string $eventName, $payload = null): array
    {
        $results = [];

        if (isset(self::$listeners[$eventName])) {
            foreach (self::$listeners[$eventName] as $listener) {
                $results[] = call_user_func($listener['callback'], $payload);
            }
        }

        return $results;
    }

    /**
     * Check if an event has any listeners.
     * 
     * @param string $eventName
     * @return bool
     */
    public static function hasListeners(string $eventName): bool
    {
        return !empty(self::$listeners[$eventName]);
    }

    /**
     * Remove all listeners for an event or all events.
     * 
     * @param string|null $eventName
     * @return void
     */
    public static function clear(string $eventName = null): void
    {
        if ($eventName) {
            unset(self::$listeners[$eventName]);
        } else {
            self::$listeners = [];
        }
    }
}
