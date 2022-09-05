<?php

namespace ipl\Stdlib\Contract;

use Countable;

interface Paginatable extends Countable
{
    /**
     * Get whether a limit is set
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
     * @param int|null $limit Maximum number of items to return. If you want to disable the limit,
     *                        it is best practice to use null or a negative value
     *
     * @return $this
     */
    public function limit($limit);

    /**
     * Get whether an offset is set
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
     * @param int|null $offset Start result set after this many rows. If you want to disable the offset,
     *                         it is best practice to use null or a negative value
     *
     * @return $this
     */
    public function offset($offset);
}
