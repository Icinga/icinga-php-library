<?php

namespace ipl\Scheduler\Common;

use Ramsey\Uuid\UuidInterface;
use React\EventLoop\TimerInterface;
use SplObjectStorage;

trait Timers
{
    /** @var SplObjectStorage */
    protected $timers;

    /**
     * Set a timer for the given UUID
     *
     * **Example Usage:**
     *
     * ```php
     * $timers->attachTimer($uuid, Loop::addTimer($interval, $callback));
     * ```
     *
     * @param UuidInterface $uuid
     * @param TimerInterface $timer
     *
     * @return $this
     */
    protected function attachTimer(UuidInterface $uuid, TimerInterface $timer): self
    {
        $this->timers->attach($uuid, $timer);

        return $this;
    }

    /**
     * Detach and return the timer for the given UUID, if any
     *
     * **Example Usage:**
     *
     * ```php
     * Loop::cancelTimer($timers->detachTimer($uuid));
     * ```
     *
     * @param UuidInterface $uuid
     *
     * @return ?TimerInterface
     */
    protected function detachTimer(UuidInterface $uuid): ?TimerInterface
    {
        if (! $this->timers->contains($uuid)) {
            return null;
        }

        $timer = $this->timers->offsetGet($uuid);

        $this->timers->detach($uuid);

        return $timer;
    }
}
