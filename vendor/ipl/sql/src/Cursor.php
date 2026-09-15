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
    protected Connection $db;

    /** @var Select */
    protected Select $select;

    /** @var array */
    protected array $fetchModeAndArgs = [];

    /**
     * Create a new cursor for the given connection and query
     *
     * @param Connection $db
     * @param Select $select
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
    public function getFetchMode(): array
    {
        return $this->fetchModeAndArgs;
    }

    /**
     * Set the fetch mode
     *
     * @param int $fetchMode Fetch mode as one of the PDO fetch mode constants.
     *   Please see {@link https://www.php.net/manual/en/pdostatement.setfetchmode} for details
     * @param mixed ...$args Fetch mode arguments
     *
     * @return $this
     */
    public function setFetchMode(int $fetchMode, ...$args): static
    {
        array_unshift($args, $fetchMode);

        $this->fetchModeAndArgs = $args;

        return $this;
    }

    public function getIterator(): Traversable
    {
        return $this->db->yieldAll($this->select, ...$this->getFetchMode());
    }

    public function hasLimit(): bool
    {
        return $this->select->hasLimit();
    }

    public function getLimit(): ?int
    {
        return $this->select->getLimit();
    }

    public function limit(?int $limit): static
    {
        $this->select->limit($limit);

        return $this;
    }

    public function hasOffset(): bool
    {
        return $this->select->hasOffset();
    }

    public function getOffset(): ?int
    {
        return $this->select->getOffset();
    }

    public function offset(?int $offset): static
    {
        $this->select->offset($offset);

        return $this;
    }

    public function count(): int
    {
        return $this->db->select($this->select->getCountQuery())->fetchColumn(0);
    }
}
