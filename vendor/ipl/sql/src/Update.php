<?php

namespace ipl\Sql;

use InvalidArgumentException;

use function ipl\Stdlib\arrayval;

/**
 * SQL UPDATE query
 */
class Update implements CommonTableExpressionInterface, WhereInterface
{
    use CommonTableExpression;
    use Where;

    /** @var array|null The table for the UPDATE query */
    protected $table;

    /** @var array|null The columns to update in terms of column-value pairs */
    protected $set = [];

    /**
     * Get the table for the UPDATE query
     *
     * @return array|null
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Set the table for the UPDATE query
     *
     * Note that this method does NOT quote the table you specify for the UPDATE.
     * If you allow user input here, you must protected yourself against SQL injection using
     * {@link Connection::quoteIdentifier()} for the table names passed to this method.
     * If you are using special table names, e.g. reserved keywords for your DBMS, you are required to use
     * {@link Connection::quoteIdentifier()} as well.
     *
     * @param string|array $table The table to update. The table specification must be in one of the
     *                            following formats: 'table', 'table alias', ['alias' => 'table']
     *
     * @return $this
     */
    public function table($table)
    {
        $this->table = is_array($table) ? $table : [$table];

        return $this;
    }

    /**
     * Get the columns to update in terms of column-value pairs
     *
     * @return array|null
     */
    public function getSet()
    {
        return $this->set;
    }

    /**
     * Set the columns to update in terms of column-value pairs
     *
     * Values may either be plain or expressions or scalar subqueries.
     *
     * Note that this method does NOT quote the columns you specify for the UPDATE.
     * If you allow user input here, you must protected yourself against SQL injection using
     * {@link Connection::quoteIdentifier()} for the column names passed to this method.
     * If you are using special column names, e.g. reserved keywords for your DBMS, you are required to use
     * {@link Connection::quoteIdentifier()} as well.
     *
     * @param iterable $set Associative set of column-value pairs
     *
     * @return $this
     *
     * @throws InvalidArgumentException If set type is invalid
     */
    public function set($set)
    {
        $this->set = arrayval($set);

        return $this;
    }

    public function __clone()
    {
        $this->cloneCte();
        $this->cloneWhere();

        foreach ($this->set as &$value) {
            if ($value instanceof ExpressionInterface || $value instanceof Select) {
                $value = clone $value;
            }
        }
        unset($value);
    }
}
