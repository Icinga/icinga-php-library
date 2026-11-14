<?php

namespace ipl\Scheduler\Contract;

use Ramsey\Uuid\UuidInterface;
use React\Promise\PromiseInterface;

/**
 * Contract for a schedulable task
 *
 * A task encapsulates a named, uniquely identified unit of work that the
 * scheduler can execute via {@see Task::run()}.
 */
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
     * Execute this task and return a promise for the result
     *
     * There are two valid implementation patterns:
     *
     * - Async: commit work to the event loop and return a *pending* promise
     *   that resolves or rejects upon completion. This is the preferred pattern
     *   for I/O-bound or long-running work.
     *
     * - Synchronous: return an already-resolved or already-rejected promise
     *   when the result is known immediately (e.g. from cache or trivial logic).
     *
     * Either way, implementations MUST NOT perform blocking I/O or CPU-intensive
     * work that would stall the event loop.
     *
     * @return PromiseInterface<mixed>
     */
    public function run(): PromiseInterface;
}
