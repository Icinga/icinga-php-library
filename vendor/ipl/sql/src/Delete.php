<?php

namespace ipl\Sql;

/**
 * SQL DELETE query
 */
class Delete implements CommonTableExpressionInterface, WhereInterface
{
    use CommonTableExpression;
    use Where;

    /** @var ?array The FROM part of the DELETE query */
    protected ?array $from = null;

    /**
     * Get the FROM part of the DELETE query
     *
     * @return ?array
     */
    public function getFrom(): ?array
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
     * @param string|array $table The table to delete data from. The table specification must be in one of the following
     *   formats: 'table', 'table alias', ['alias' => 'table']
     *
     * @return $this
     */
    public function from(string|array $table): static
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
