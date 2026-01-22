<?php

namespace ipl\Sql;

/**
 * Interface for pagination via {@link limit()} and {@link offset()}
 */
interface LimitOffsetInterface
{
    /**
     * Get whether a limit is configured
     *
     * @return bool
     */
    public function hasLimit();

    /**
     * Get the limit
     *
     * @return int|null
     */
    public function getLimit();

    /**
     * Set the limit
     *
     * @param int|null $limit Maximum number of items to return.
     *                        If you want to disable the limit, use null or a negative value
     *
     * @return $this
     */
    public function limit($limit);

    /**
     * Reset the limit
     *
     * @return $this
     */
    public function resetLimit();

    /**
     * Get whether an offset is configured
     *
     * @return bool
     */
    public function hasOffset();

    /**
     * Get the offset
     *
     * @return int|null
     */
    public function getOffset();

    /**
     * Set the offset
     *
     * @param int|null $offset Start result set after this many rows.
     *                         If you want to disable the offset, use null, 0, or a negative value
     *
     * @return $this
     */
    public function offset($offset);

    /**
     * Reset the offset
     *
     * @return $this
     */
    public function resetOffset();
}
