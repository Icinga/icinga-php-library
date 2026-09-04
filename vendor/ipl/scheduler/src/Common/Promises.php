<?php

namespace ipl\Scheduler\Common;

use ArrayObject;
use InvalidArgumentException;
use Ramsey\Uuid\UuidInterface;
use React\Promise\PromiseInterface;
use SplObjectStorage;

/**
 * Tracks pending {@see PromiseInterface promises} per UUID
 *
 * Provides helpers to add, remove, and detach promises keyed by a {@see UuidInterface}.
 */
trait Promises
{
    /** @var SplObjectStorage<UuidInterface, ArrayObject<int, PromiseInterface<mixed>>> */
    protected SplObjectStorage $promises;

    /**
     * Add the given promise for the specified UUID
     *
     * **Example Usage:**
     *
     *     $promise = work();
     *     $promises->addPromise($uuid, $promise);
     *
     * @param UuidInterface $uuid
     * @param PromiseInterface<mixed> $promise
     *
     * @return $this
     */
    protected function addPromise(UuidInterface $uuid, PromiseInterface $promise): static
    {
        if (! $this->promises->offsetExists($uuid)) {
            $this->promises->offsetSet($uuid, new ArrayObject());
        }

        $this->promises[$uuid][] = $promise;

        return $this;
    }

    /**
     * Remove the given promise for the specified UUID
     *
     * **Example Usage:**
     *
     *     $promise->finally(function () use ($uuid, $promise) {
     *         $promises->removePromise($uuid, $promise);
     *     })
     *
     * @param UuidInterface $uuid
     * @param PromiseInterface<mixed> $promise
     *
     * @return $this
     *
     * @throws InvalidArgumentException If the given UUID doesn't have any registered promises or when the specified
     *                                  UUID promises doesn't contain the provided promise
     */
    protected function removePromise(UuidInterface $uuid, PromiseInterface $promise): static
    {
        if (! $this->promises->offsetExists($uuid)) {
            throw new InvalidArgumentException(
                sprintf('There are no registered promises for UUID %s', $uuid->toString())
            );
        }

        foreach ($this->promises[$uuid] as $k => $v) {
            if ($v === $promise) {
                unset($this->promises[$uuid][$k]);

                return $this;
            }
        }

        throw new InvalidArgumentException(
            sprintf('There is no such promise for UUID %s', $uuid->toString())
        );
    }

    /**
     * Detach and return promises for the given UUID, if any
     *
     * **Example Usage:**
     *
     *     foreach ($promises->detachPromises($uuid) as $promise) {
     *         $promise->cancel();
     *     }
     *
     * @param UuidInterface $uuid
     *
     * @return array<int, PromiseInterface<mixed>>
     */
    protected function detachPromises(UuidInterface $uuid): array
    {
        if (! $this->promises->offsetExists($uuid)) {
            return [];
        }

        $promises = $this->promises[$uuid];
        $this->promises->offsetUnset($uuid);

        return $promises->getArrayCopy();
    }
}
