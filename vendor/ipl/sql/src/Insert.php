<?php

namespace ipl\Sql;

use InvalidArgumentException;

use function ipl\Stdlib\arrayval;

/**
 * SQL INSERT query
 */
class Insert implements CommonTableExpressionInterface
{
    use CommonTableExpression;

    /** @var string|null The table for the INSERT INTO query */
    protected $into;

    /** @var array|null The columns for which the query provides values */
    protected $columns;

    /** @var array|null The values to insert */
    protected $values;

    /** @var Select|null The select query for INSERT INTO ... SELECT queries */
    protected $select;

    /**
     * Get the table for the INSERT INTo query
     *
     * @return string|null
     */
    public function getInto()
    {
        return $this->into;
    }

    /**
     * Set the table for the INSERT INTO query
     *
     * Note that this method does NOT quote the table you specify for the INSERT INTO.
     * If you allow user input here, you must protected yourself against SQL injection using
     * {@link Connection::quoteIdentifier()} for the table name passed to this method.
     * If you are using special table names, e.g. reserved keywords for your DBMS, you are required to use
     * {@link Connection::quoteIdentifier()} as well.
     *
     * @param string $table The table to insert data into. The table specification must be in one of the following
     *                      formats: 'table' or 'schema.table'
     *
     * @return $this
     */
    public function into($table)
    {
        $this->into = $table;

        return $this;
    }

    /**
     * Get the columns for which the statement provides values
     *
     * @return array
     */
    public function getColumns()
    {
        if (! empty($this->columns)) {
            return array_keys($this->columns);
        }

        if (! empty($this->values)) {
            return array_keys($this->values);
        }

        return [];
    }

    /**
     * Set the columns for which the query provides values
     *
     * Note that this method does NOT quote the columns you specify for the INSERT INTO.
     * If you allow user input here, you must protected yourself against SQL injection using
     * {@link Connection::quoteIdentifier()} for the column names passed to this method.
     * If you are using special column names, e.g. reserved keywords for your DBMS, you are required to use
     * {@link Connection::quoteIdentifier()} as well.
     *
     * If you do not set the columns for which the query provides values using this method, you must pass the values to
     * {@link values()} in terms of column-value pairs in order to provide the column names.
     *
     * @param array $columns
     *
     * @return $this
     */
    public function columns(array $columns)
    {
        $this->columns = array_flip($columns);

        return $this;
    }

    /**
     * Get the values to insert
     *
     * @return array
     */
    public function getValues()
    {
        return array_values($this->values ?: []);
    }

    /**
     * Set the values to INSERT INTO - either plain values or expressions or scalar subqueries
     *
     * If you do not set the columns for which the query provides values using {@link columns()}, you must specify
     * the values in terms of column-value pairs in order to provide the column names. Please note that the same
     * restriction regarding quoting applies here. If you use {@link columns()} to set the columns and specify the
     * values in terms of column-value pairs, the columns from {@link columns()} will be used nonetheless.
     *
     * @param iterable $values List of values or associative set of column-value pairs
     *
     * @return $this
     *
     * @throws InvalidArgumentException If values type is invalid
     */
    public function values($values)
    {
        $this->values = arrayval($values);

        return $this;
    }

    /**
     * Create a INSERT INTO ... SELECT statement
     *
     * @param Select $select
     *
     * @return $this
     */
    public function select(Select $select)
    {
        $this->select = $select;

        return $this;
    }

    /**
     * Get the select query for the INSERT INTO ... SELECT statement
     *
     * @return Select|null
     */
    public function getSelect()
    {
        return $this->select;
    }

    public function __clone()
    {
        $this->cloneCte();

        if ($this->values !== null) {
            foreach ($this->values as &$value) {
                if ($value instanceof ExpressionInterface || $value instanceof Select) {
                    $value = clone $value;
                }
            }
            unset($value);
        }

        if ($this->select !== null) {
            $this->select = clone $this->select;
        }
    }
}
