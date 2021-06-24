<?php

namespace ipl\Orm;

use ArrayIterator;
use Iterator;
use Traversable;

class ResultSet implements Iterator
{
    protected $cache;

    protected $generator;

    protected $limit;

    protected $position;

    public function __construct(Traversable $traversable, $limit = null)
    {
        $this->cache = new ArrayIterator();
        $this->generator = $this->yieldTraversable($traversable);
        $this->limit = $limit;
    }

    public function hasMore()
    {
        return $this->generator->valid();
    }

    public function hasResult()
    {
        return $this->generator->valid();
    }

    public function current()
    {
        if ($this->position === null) {
            $this->advance();
        }

        return $this->cache->current();
    }

    public function next()
    {
        $this->cache->next();

        if (! $this->cache->valid()) {
            $this->generator->next();
            $this->advance();
        }
    }

    public function key()
    {
        if ($this->position === null) {
            $this->advance();
        }

        return $this->cache->key();
    }

    public function valid()
    {
        if ($this->limit !== null && $this->position === $this->limit) {
            return false;
        }

        return $this->cache->valid() || $this->generator->valid();
    }

    public function rewind()
    {
        $this->cache->rewind();

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

        $this->cache[$this->generator->key()] = $this->generator->current();

        // Only required on PHP 5.6, 7+ does it automatically
        $this->cache->seek($this->generator->key());

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
