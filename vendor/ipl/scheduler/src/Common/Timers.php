<?php

namespace ipl\Scheduler\Common;

use Ramsey\Uuid\UuidInterface;
use React\EventLoop\TimerInterface;
use SplObjectStorage;

/**
 * Tracks scheduled {@see TimerInterface timers} per UUID
 *
 * Provides helpers to attach and detach event-loop timers keyed by a {@see UuidInterface}.
 */
trait Timers
{
    /** @var SplObjectStorage<UuidInterface, TimerInterface> */
    protected SplObjectStorage $timers;

    /**
     * Set a timer for the given UUID
     *
     * **Example Usage:**
     *
     *     $timers->attachTimer($uuid, Loop::addTimer($interval, $callback));
     *
     * @param UuidInterface $uuid
     * @param TimerInterface $timer
     *
     * @return $this
     */
    protected function attachTimer(UuidInterface $uuid, TimerInterface $timer): static
    {
        $this->timers->offsetSet($uuid, $timer);

        return $this;
    }

    /**
     * Detach and return the timer for the given UUID, if any
     *
     * **Example Usage:**
     *
     *     Loop::cancelTimer($timers->detachTimer($uuid));
     *
     * @param UuidInterface $uuid
     *
     * @return ?TimerInterface
     */
    protected function detachTimer(UuidInterface $uuid): ?TimerInterface
    {
        if (! $this->timers->offsetExists($uuid)) {
            return null;
        }

        $timer = $this->timers->offsetGet($uuid);

        $this->timers->offsetUnset($uuid);

        return $timer;
    }
}
