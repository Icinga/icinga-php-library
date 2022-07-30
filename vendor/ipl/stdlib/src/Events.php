<?php

namespace ipl\Stdlib;

use Evenement\EventEmitterTrait;
use InvalidArgumentException;

trait Events
{
    use EventEmitterTrait {
        EventEmitterTrait::on as private evenementUnvalidatedOn;
    }

    /** @var array */
    protected $eventsEmittedOnce = [];

    /**
     * @param string $event
     * @param array $arguments
     */
    protected function emitOnce($event, array $arguments = [])
    {
        if (! isset($this->eventsEmittedOnce[$event])) {
            $this->eventsEmittedOnce[$event] = true;
            $this->emit($event, $arguments);
        }
    }

    /**
     * @param string $event
     * @param callable $listener
     * @return $this
     */
    public function on($event, callable $listener)
    {
        $this->assertValidEvent($event);
        $this->evenementUnvalidatedOn($event, $listener);

        return $this;
    }

    protected function assertValidEvent($event)
    {
        if (! $this->isValidEvent($event)) {
            throw new InvalidArgumentException("$event is not a valid event");
        }
    }

    /**
     * @param string $event
     * @return bool
     */
    public function isValidEvent($event)
    {
        return true;
    }
}
