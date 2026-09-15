<?php

namespace ipl\Orm;

use ArrayIterator;
use Countable;
use Generator;
use Iterator;
use RuntimeException;
use Traversable;

/**
 * @template TRow of Model
 * @implements Iterator<int, TRow>
 */
class ResultSet implements Iterator, Countable
{
    /** @var ArrayIterator<int, TRow> */
    protected ArrayIterator $cache;

    /** @var bool Whether cache is disabled */
    protected bool $isCacheDisabled = false;

    /**
     * @var Generator<mixed, int, TRow, void>
     * @phpstan-var Generator<int, TRow, mixed, void>
     */
    protected Generator $generator;

    protected ?int $limit;

    protected ?int $position = null;

    protected ?int $count = null;

    /**
     * Create a new result set from the given traversable
     *
     * @param Traversable<int, TRow> $traversable
     * @param ?int $limit
     */
    public function __construct(Traversable $traversable, ?int $limit = null)
    {
        $this->cache = new ArrayIterator();
        $this->generator = $this->yieldTraversable($traversable);
        $this->limit = $limit;
    }

    /**
     * Create a new result set from the given query
     *
     * @template TQueryRow of Model
     *
     * @param Query<TQueryRow> $query
     *
     * @return static<TQueryRow>
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
    public function disableCache(): static
    {
        $this->isCacheDisabled = true;

        return $this;
    }

    public function hasMore(): bool
    {
        return $this->generator->valid();
    }

    public function hasResult(): bool
    {
        return $this->generator->valid();
    }

    /** @return TRow */
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
            // Raise count during the first loop only after each iteration, so
            // that it is synchronized with how many times a loop has been run.
            $this->count += 1;

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
            $this->count = 0;
        } else {
            $this->position = 0;
        }
    }

    public function count(): int
    {
        if (! $this->isCacheDisabled && $this->count === null && $this->cache->count() === 0) {
            foreach ($this as $_) {
                // exhaust the generator and establish the cache
            }
        } elseif (
            $this->count === null
            || ($this->limit === null || $this->count < $this->limit) && $this->hasMore()
        ) {
            throw new RuntimeException('Cannot count result set while it is not fully iterated');
        }

        return $this->count;
    }

    protected function advance(): void
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

    /**
     * Yield the given traversable
     *
     * @param Traversable<int, TRow> $traversable
     *
     * @return Generator<mixed, int, TRow, void>
     * @phpstan-return Generator<int, TRow, mixed, void>
     */
    protected function yieldTraversable(Traversable $traversable)
    {
        foreach ($traversable as $key => $value) {
            yield $key => $value;
        }
    }
}
