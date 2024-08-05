<?php

namespace ipl\Scheduler\Contract;

use Ramsey\Uuid\UuidInterface;
use React\Promise\ExtendedPromiseInterface;

interface Task
{
    /**
     * Get the name of this task
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get unique identifier of this task
     *
     * @return UuidInterface
     */
    public function getUuid(): UuidInterface;

    /**
     * Get the description of this task
     *
     * @return ?string
     */
    public function getDescription(): ?string;

    /**
     * Run this tasks operations
     *
     * This commits the actions in a non-blocking fashion to the event loop and yields a deferred promise
     *
     * @return ExtendedPromiseInterface
     */
    public function run(): ExtendedPromiseInterface;
}
