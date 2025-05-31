<?php

namespace ipl\Sql;

use ipl\Stdlib\Contract\Paginatable;
use IteratorAggregate;
use Traversable;

/**
 * Cursor for ipl SQL queries
 */
class Cursor implements IteratorAggregate, Paginatable
{
    /** @var Connection */
    protected $db;

    /** @var Select */
    protected $select;

    /** @var array */
    protected $fetchModeAndArgs = [];

    /**
     * Create a new cursor for the given connection and query
     *
     * @param Connection $db
     * @param Select     $select
     */
    public function __construct(Connection $db, Select $select)
    {
        $this->db = $db;
        $this->select = $select;
    }

    /**
     * Get the fetch mode
     *
     * @return array
     */
    public function getFetchMode()
    {
        return $this->fetchModeAndArgs;
    }

    /**
     * Set the fetch mode
     *
     * @param int   $fetchMode Fetch mode as one of the PDO fetch mode constants.
     *                         Please see {@link https://www.php.net/manual/en/pdostatement.setfetchmode} for details
     * @param mixed ...$args   Fetch mode arguments
     *
     * @return $this
     */
    public function setFetchMode($fetchMode, ...$args)
    {
        array_unshift($args, $fetchMode);

        $this->fetchModeAndArgs = $args;

        return $this;
    }

    public function getIterator(): Traversable
    {
        return $this->db->yieldAll($this->select, ...$this->getFetchMode());
    }

    public function hasLimit()
    {
        return $this->select->hasLimit();
    }

    public function getLimit()
    {
        return $this->select->getLimit();
    }

    public function limit($limit)
    {
        $this->select->limit($limit);

        return $this;
    }

    public function hasOffset()
    {
        return $this->select->hasOffset();
    }

    public function getOffset()
    {
        return $this->select->getOffset();
    }

    public function offset($offset)
    {
        $this->select->offset($offset);

        return $this;
    }

    public function count(): int
    {
        return $this->db->select($this->select->getCountQuery())->fetchColumn(0);
    }
}
