<?php

namespace ipl\Orm;

use ArrayIterator;
use Iterator;
use Traversable;

class ResultSet implements Iterator
{
    protected $cache;

    /** @var bool Whether cache is disabled */
    protected $isCacheDisabled = false;

    protected $generator;

    protected $limit;

    protected $position;

    public function __construct(Traversable $traversable, $limit = null)
    {
        $this->cache = new ArrayIterator();
        $this->generator = $this->yieldTraversable($traversable);
        $this->limit = $limit;
    }

    /**
     * Create a new result set from the given query
     *
     * @param Query $query
     *
     * @return static
     */
    public static function fromQuery(Query $query)
    {
        return new static($query->yieldResults(), $query->getLimit());
    }

    /**
     * Do not cache query result
     *
     * ResultSet instance can only be iterated once
     *
     * @return $this
     */
    public function disableCache()
    {
        $this->isCacheDisabled = true;

        return $this;
    }

    public function hasMore()
    {
        return $this->generator->valid();
    }

    public function hasResult()
    {
        return $this->generator->valid();
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        if ($this->position === null) {
            $this->advance();
        }

        return $this->isCacheDisabled ? $this->generator->current() : $this->cache->current();
    }

    public function next(): void
    {
        if (! $this->isCacheDisabled) {
            $this->cache->next();
        }

        if ($this->isCacheDisabled || ! $this->cache->valid()) {
            $this->generator->next();
            $this->advance();
        } else {
            $this->position += 1;
        }
    }

    public function key(): int
    {
        if ($this->position === null) {
            $this->advance();
        }

        return $this->isCacheDisabled ? $this->generator->key() : $this->cache->key();
    }

    public function valid(): bool
    {
        if ($this->limit !== null && $this->position === $this->limit) {
            return false;
        }

        return $this->cache->valid() || $this->generator->valid();
    }

    public function rewind(): void
    {
        if (! $this->isCacheDisabled) {
            $this->cache->rewind();
        }

        if ($this->position === null) {
            $this->advance();
        } else {
            $this->position = 0;
        }
    }

    protected function advance()
    {
        if (! $this->generator->valid()) {
            return;
        }

        if (! $this->isCacheDisabled) {
            $this->cache[$this->generator->key()] = $this->generator->current();
        }

        if ($this->position === null) {
            $this->position = 0;
        } else {
            $this->position += 1;
        }
    }

    protected function yieldTraversable(Traversable $traversable)
    {
        foreach ($traversable as $key => $value) {
            yield $key => $value;
        }
    }
}
