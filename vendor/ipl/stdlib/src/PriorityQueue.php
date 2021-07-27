<?php

namespace ipl\Stdlib;

use Generator;
use SplPriorityQueue;

/**
 * Stable priority queue that also maintains insertion order for items with the same priority
 */
class PriorityQueue extends SplPriorityQueue
{
    protected $serial = PHP_INT_MAX;

    /**
     * @inheritDoc
     *
     * Maintains insertion order for items with the same priority.
     */
    public function insert($value, $priority)
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
            yield $item['priority'][0] => $item['data'];
        }
    }
}
