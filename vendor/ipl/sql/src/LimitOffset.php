<?php

namespace ipl\Sql;

/**
 * Implementation for the {@link LimitOffsetInterface} to allow pagination via {@link limit()} and {@link offset()}
 */
trait LimitOffset
{
    /**
     * The maximum number of how many items to return
     *
     * If unset or lower than 0, no limit will be applied.
     *
     * @var int|null
     */
    protected $limit;

    /**
     * Offset from where to start the result set
     *
     * If unset or lower than 0, the result set will start from the beginning.
     *
     * @var int|null
     */
    protected $offset;

    public function hasLimit()
    {
        return $this->limit !== null;
    }

    public function getLimit()
    {
        return $this->limit;
    }

    public function limit($limit)
    {
        if ($limit !== null) {
            $limit = (int) $limit;
            if ($limit < 0) {
                $limit = null;
            }
        }

        $this->limit = $limit;

        return $this;
    }

    public function resetLimit()
    {
        $this->limit = null;

        return $this;
    }

    public function hasOffset()
    {
        return $this->offset !== null;
    }

    public function getOffset()
    {
        return $this->offset;
    }

    public function offset($offset)
    {
        if ($offset !== null) {
            $offset = (int) $offset;
            if ($offset <= 0) {
                $offset = null;
            }
        }

        $this->offset = $offset;

        return $this;
    }

    public function resetOffset()
    {
        $this->offset = null;

        return $this;
    }
}
