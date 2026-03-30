<?php

namespace ipl\Stdlib;

use Evenement\EventEmitterTrait;
use InvalidArgumentException;

/**
 * Register listeners and emit named events with optional event-name validation
 */
trait Events
{
    use EventEmitterTrait {
        EventEmitterTrait::on as private evenementUnvalidatedOn;
    }

    /** @var array<string, true> */
    protected array $eventsEmittedOnce = [];

    /**
     * Emit the given event at most once, ignoring subsequent calls
     *
     * @param string $event
     * @param array $arguments
     *
     * @return void
     */
    protected function emitOnce(string $event, array $arguments = []): void
    {
        if (! isset($this->eventsEmittedOnce[$event])) {
            $this->eventsEmittedOnce[$event] = true;
            $this->emit($event, $arguments);
        }
    }

    /**
     * Register a listener for the given event, validating the event name first
     *
     * @param string $event
     * @param callable $listener
     *
     * @return $this
     *
     * @throws InvalidArgumentException If the event name is not valid
     */
    public function on($event, callable $listener): static
    {
        $this->assertValidEvent($event);
        $this->evenementUnvalidatedOn($event, $listener);

        return $this;
    }

    /**
     * Assert that the given event name is valid
     *
     * @param string $event
     *
     * @return void
     *
     * @throws InvalidArgumentException If the event name is not valid
     */
    protected function assertValidEvent(string $event): void
    {
        if (! $this->isValidEvent($event)) {
            throw new InvalidArgumentException("$event is not a valid event");
        }
    }

    /**
     * Check whether the given event name is valid
     *
     * @param string $event
     *
     * @return bool
     */
    public function isValidEvent($event)
    {
        return true;
    }
}
