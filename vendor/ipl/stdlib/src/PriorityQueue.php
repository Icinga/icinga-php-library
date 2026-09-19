<?php

namespace ipl\Stdlib;

use Generator;
use SplPriorityQueue;

/**
 * Stable priority queue that also maintains insertion order for items with the same priority
 *
 * @template TPriority
 * @template TValue
 * @extends SplPriorityQueue<array{TPriority, int}, TValue>
 */
class PriorityQueue extends SplPriorityQueue
{
    /** @var int Decreasing insertion counter for stable ordering at equal priorities */
    protected int $serial = PHP_INT_MAX;

    /**
     * Insert an element in the queue, maintaining insertion order for equal priorities
     *
     * @param TValue $value
     * @param TPriority $priority
     *
     * @return true
     */
    public function insert(mixed $value, mixed $priority): true
    {
        return parent::insert($value, [$priority, $this->serial--]);
    }

    /**
     * Yield all items as priority-value pairs
     *
     * @return Generator<TPriority, TValue>
     */
    public function yieldAll(): Generator
    {
        // Clone the queue because SplPriorityQueue acts as a heap and removes items upon iteration.
        $queue = clone $this;

        $queue->setExtractFlags(static::EXTR_BOTH);

        foreach ($queue as $item) {
            /** @var array{priority: array{0: TPriority, 1: int}, data: TValue} $item */
            yield $item['priority'][0] => $item['data'];
        }
    }
}
