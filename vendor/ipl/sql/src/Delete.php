<?php

namespace ipl\Sql;

/**
 * SQL DELETE query
 */
class Delete implements CommonTableExpressionInterface, WhereInterface
{
    use CommonTableExpression;
    use Where;

    /** @var array|null The FROM part of the DELETE query */
    protected $from;

    /**
     * Get the FROM part of the DELETE query
     *
     * @return array|null
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * Set the FROM part of the DELETE query
     *
     * Note that this method does NOT quote the table you specify for the DELETE FROM.
     * If you allow user input here, you must protected yourself against SQL injection using
     * {@link Connection::quoteIdentifier()} for the table names passed to this method.
     * If you are using special table names, e.g. reserved keywords for your DBMS, you are required to use
     * {@link Connection::quoteIdentifier()} as well.
     *
     * @param string|array $table The table to delete data from. The table specification must be in one of the
     *                            following formats: 'table', 'table alias', ['alias' => 'table']
     *
     * @return $this
     */
    public function from($table)
    {
        $this->from = ! is_array($table) ? [$table] : $table;

        return $this;
    }

    public function __clone()
    {
        $this->cloneCte();
        $this->cloneWhere();
    }
}
