<?php

namespace ipl\Scheduler;

use DateTime;
use InvalidArgumentException;
use ipl\Scheduler\Common\Promises;
use ipl\Scheduler\Common\Timers;
use ipl\Scheduler\Contract\Frequency;
use ipl\Scheduler\Contract\Task;
use ipl\Stdlib\Events;
use React\EventLoop\Loop;
use React\Promise;
use React\Promise\ExtendedPromiseInterface;
use SplObjectStorage;
use Throwable;

class Scheduler
{
    use Events;
    use Timers;
    use Promises;

    /**
     * Event raised when a {@link Task task} is canceled
     *
     * The task and its pending operations as an array of canceled {@link ExtendedPromiseInterface promise}s
     * are passed as parameters to the event callbacks.
     *
     * **Example usage:**
     *
     * ```php
     * $scheduler->on($scheduler::ON_TASK_CANCEL, function (Task $task, array $_) use ($logger) {
     *     $logger->info(sprintf('Task %s cancelled', $task->getName()));
     * });
     * ```
     */
    public const ON_TASK_CANCEL = 'task-cancel';

    /**
     * Event raised when an operation of a {@link Task task} is done
     *
     * The task and the operation result are passed as parameters to the event callbacks.
     *
     * **Example usage:**
     *
     * ```php
     * $scheduler->on($scheduler::ON_TASK_DONE, function (Task $task, $result) use ($logger) {
     *     $logger->info(sprintf('Operation of task %s done: %s', $task->getName(), $result));
     * });
     * ```
     */
    public const ON_TASK_DONE = 'task-done';

    /**
     * Event raised when an operation of a {@link Task task} failed
     *
     * The task and the {@link Throwable reason} why the operation failed
     * are passed as parameters to the event callbacks.
     *
     * **Example usage:**
     *
     * ```php
     * $scheduler->on($scheduler::ON_TASK_FAILED, function (Task $task, Throwable $e) use ($logger) {
     *     $logger->error(
     *         sprintf('Operation of task %s failed: %s', $task->getName(), $e),
     *         ['exception' => $e]
     *     );
     * });
     * ```
     */
    public const ON_TASK_FAILED = 'task-failed';

    /**
     * Event raised when a {@link Task task} operation is scheduled
     *
     * The task and the {@link DateTime time} when it should run
     * are passed as parameters to the event callbacks.
     *
     * **Example usage:**
     *
     * ```php
     * $scheduler->on($scheduler::ON_TASK_SCHEDULED, function (Task $task, DateTime $dateTime) use ($logger) {
     *     $logger->info(sprintf(
     *         'Scheduling task %s to run at %s',
     *         $task->getName(),
     *         IntlDateFormatter::formatObject($dateTime)
     *     ));
     * });
     * ```
     */
    public const ON_TASK_SCHEDULED = 'task-scheduled';

    /**
     * Event raised upon operation of a {@link Task task}
     *
     * The task and the possibly not yet completed result of the operation as a {@link ExtendedPromiseInterface promise}
     * are passed as parameters to the event callbacks.
     *
     * **Example usage:**
     *
     * ```php
     * $scheduler->on($scheduler::ON_TASK_OPERATION, function (Task $task, ExtendedPromiseInterface $_) use ($logger) {
     *     $logger->info(sprintf('Task %s operating', $task->getName()));
     * });
     * ```
     */
    public const ON_TASK_RUN = 'task-run';

    /**
     * Event raised when a {@see Task task} is expired
     *
     * The task and the {@see DateTime expire time} are passed as parameters to the event callbacks.
     * Note that the expiration time is the first time that is considered expired based on the frequency
     * of the task and can be later than the specified end time.
     *
     * **Example usage:**
     *
     * ```php
     * $scheduler->on(Scheduler::ON_TASK_EXPIRED, function (Task $task, DateTime $dateTime) use ($logger) {
     *     $logger->info(sprintf('Removing expired task %s at %s', $task->getName(), $dateTime->format('Y-m-d H:i:s')));
     * });
     * ```
     */
    public const ON_TASK_EXPIRED = 'task-expired';

    /** @var SplObjectStorage<Task, null> The scheduled tasks of this scheduler */
    protected $tasks;

    public function __construct()
    {
        $this->tasks = new SplObjectStorage();

        $this->promises = new SplObjectStorage();
        $this->timers = new SplObjectStorage();

        $this->init();
    }

    /**
     * Initialize this scheduler
     */
    protected function init(): void
    {
    }

    /**
     * Remove and cancel the given task
     *
     * @param Task $task
     *
     * @return $this
     *
     * @throws InvalidArgumentException If the given task isn't scheduled
     */
    public function remove(Task $task): self
    {
        if (! $this->hasTask($task)) {
            throw new InvalidArgumentException(sprintf('Task %s not scheduled', $task->getName()));
        }

        $this->cancelTask($task);

        $this->tasks->offsetUnset($task);

        return $this;
    }

    /**
     * Remove and cancel all tasks
     *
     * @return $this
     */
    public function removeTasks(): self
    {
        foreach ($this->tasks as $task) {
            $this->cancelTask($task);
        }

        $this->tasks = new SplObjectStorage();

        return $this;
    }

    /**
     * Get whether the specified task is scheduled
     *
     * @param Task $task
     *
     * @return bool
     */
    public function hasTask(Task $task): bool
    {
        return $this->tasks->offsetExists($task);
    }

    /**
     * Schedule the given task based on the specified frequency
     *
     * @param Task $task
     * @param Frequency $frequency
     *
     * @return $this
     */
    public function schedule(Task $task, Frequency $frequency): self
    {
        $now = new DateTime();
        if ($frequency->isExpired($now)) {
            return $this;
        }

        if ($frequency->isDue($now)) {
            Loop::futureTick(function () use ($task): void {
                $promise = $this->runTask($task);
                $this->emit(static::ON_TASK_RUN, [$task, $promise]);
            });
            $this->emit(static::ON_TASK_SCHEDULED, [$task, $now]);

            if ($frequency instanceof OneOff) {
                return $this;
            }
        }

        $loop = function () use (&$loop, $task, $frequency): void {
            $promise = $this->runTask($task);
            $this->emit(static::ON_TASK_RUN, [$task, $promise]);

            $now = new DateTime();
            $nextDue = $frequency->getNextDue($now);
            if ($frequency instanceof OneOff || $frequency->isExpired($nextDue)) {
                $removeTask = function () use ($task, $nextDue): void {
                    $this->remove($task);
                    $this->emit(static::ON_TASK_EXPIRED, [$task, $nextDue]);
                };

                if ($this->promises->offsetExists($task->getUuid())) {
                    $pendingPromises = (array) $this->promises->offsetGet($task->getUuid());
                    Promise\all($pendingPromises)->always($removeTask);
                } else {
                    $removeTask();
                }

                return;
            }

            $this->attachTimer(
                $task->getUuid(),
                Loop::addTimer($nextDue->getTimestamp() - $now->getTimestamp(), $loop)
            );
            $this->emit(static::ON_TASK_SCHEDULED, [$task, $nextDue]);
        };

        $nextDue = $frequency->getNextDue($now);
        $this->attachTimer(
            $task->getUuid(),
            Loop::addTimer($nextDue->getTimestamp() - $now->getTimestamp(), $loop)
        );
        $this->emit(static::ON_TASK_SCHEDULED, [$task, $nextDue]);

        $this->tasks->offsetSet($task);

        return $this;
    }

    public function isValidEvent(string $event): bool
    {
        $events = array_flip([
            static::ON_TASK_CANCEL,
            static::ON_TASK_DONE,
            static::ON_TASK_EXPIRED,
            static::ON_TASK_FAILED,
            static::ON_TASK_RUN,
            static::ON_TASK_SCHEDULED
        ]);

        return isset($events[$event]);
    }

    /**
     * Cancel the timer of the task and all pending operations
     *
     * @param Task $task
     */
    protected function cancelTask(Task $task): void
    {
        Loop::cancelTimer($this->detachTimer($task->getUuid()));

        /** @var ExtendedPromiseInterface[] $promises */
        $promises = $this->detachPromises($task->getUuid());
        if (! empty($promises)) {
            /** @var Promise\CancellablePromiseInterface $promise */
            foreach ($promises as $promise) {
                $promise->cancel();
            }
            $this->emit(self::ON_TASK_CANCEL, [$task, $promises]);
        }
    }

    /**
     * Runs the given task immediately and registers handlers for the returned promise
     *
     * @param Task $task
     *
     * @return ExtendedPromiseInterface
     */
    protected function runTask(Task $task): ExtendedPromiseInterface
    {
        $promise = $task->run();
        $this->addPromise($task->getUuid(), $promise);

        return $promise->then(
            function ($result) use ($task): void {
                $this->emit(self::ON_TASK_DONE, [$task, $result]);
            },
            function (Throwable $reason) use ($task): void {
                $this->emit(self::ON_TASK_FAILED, [$task, $reason]);
            }
        )->always(function () use ($task, $promise): void {
            // Unregister the promise without canceling it as it's already resolved
            $this->removePromise($task->getUuid(), $promise);
        });
    }
}
