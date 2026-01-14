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
    /** @var int */
    protected $serial = PHP_INT_MAX;

    /**
     * Inserts an element in the queue by sifting it up.
     *
     * Maintains insertion order for items with the same priority.
     *
     * @param TValue $value
     * @param TPriority $priority
     *
     * @return true
     */
    public function insert($value, $priority): true
    {
        return parent::insert($value, [$priority, $this->serial--]);
    }

    /**
     * Yield all items as priority-value pairs
     *
     * @return Generator
     */
    public function yieldAll()
    {
        // Clone queue because the SplPriorityQueue acts as a heap and thus items are removed upon iteration
        $queue = clone $this;

        $queue->setExtractFlags(static::EXTR_BOTH);

        foreach ($queue as $item) {
            /** @var array{priority: array{0: TPriority, 1: int}, data: TValue} $item */
            yield $item['priority'][0] => $item['data'];
        }
    }
}
