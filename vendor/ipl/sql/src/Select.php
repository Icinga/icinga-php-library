<?php

namespace ipl\Sql;

/**
 * SQL SELECT query
 */
class Select implements CommonTableExpressionInterface, LimitOffsetInterface, OrderByInterface, WhereInterface
{
    use CommonTableExpression;
    use LimitOffset;
    use OrderBy;
    use Where;

    /** @var bool Whether the query is DISTINCT */
    protected bool $distinct = false;

    /** @var ?array The columns for the SELECT query */
    protected ?array $columns = null;

    /** @var ?array FROM part of the query, i.e. the table names to select data from */
    protected ?array $from = null;

    /**
     * The tables to JOIN
     *
     * [
     *   [ $joinType, $tableName, $condition ],
     *   ...
     * ]
     *
     * @var ?array
     */
    protected ?array $join = null;

    /** @var ?array The columns for the GROUP BY part of the query */
    protected ?array $groupBy = null;

    /** @var ?array Internal representation for the HAVING part of the query */
    protected ?array $having = null;

    /**
     * The queries to UNION
     *
     * [
     *   [ new Select(), (bool) 'UNION ALL' ],
     *   ...
     * ]
     *
     * @var ?array
     */
    protected ?array $union = null;

    /**
     * Get whether to SELECT DISTINCT
     *
     * @return bool
     */
    public function getDistinct(): bool
    {
        return $this->distinct;
    }

    /**
     * Set whether to SELECT DISTINCT
     *
     * @param bool $distinct
     *
     * @return $this
     */
    public function distinct(bool $distinct = true): static
    {
        $this->distinct = $distinct;

        return $this;
    }

    /**
     * Get the columns for the SELECT query
     *
     * @return array
     */
    public function getColumns(): array
    {
        return $this->columns ?: [];
    }

    /**
     * Add columns to the SELECT query
     *
     * Multiple calls to this method will not overwrite the previous set columns but append the columns to the query.
     *
     * Note that this method does NOT quote the columns you specify for the SELECT.
     * If you allow user input here, you must protected yourself against SQL injection using
     * {@link Connection::quoteIdentifier()} for the column names passed to this method.
     * If you are using special column names, e.g. reserved keywords for your DBMS, you are required to use
     * {@link Connection::quoteIdentifier()} as well.
     *
     * @param string|ExpressionInterface|self|array $columns The column(s) to add to the SELECT.
     *   The items can be any mix of the following: 'column', 'column as alias', ['alias' => 'column']
     *
     * @return $this
     */
    public function columns(string|ExpressionInterface|self|array $columns): static
    {
        if (! is_array($columns)) {
            $columns = [$columns];
        }

        $this->columns = array_merge($this->columns ?: [], $columns);

        return $this;
    }

    /**
     * Get the FROM part of the query
     *
     * @return ?array
     */
    public function getFrom(): ?array
    {
        return $this->from;
    }

    /**
     * Add a FROM part to the query
     *
     * Multiple calls to this method will not overwrite the previous set FROM part but append the tables to the FROM.
     *
     * Note that this method does NOT quote the tables you specify for the FROM.
     * If you allow user input here, you must protect yourself against SQL injection using
     * {@link Connection::quoteIdentifier()} for the table names passed to this method.
     * If you are using special table names, e.g. reserved keywords for your DBMS, you are required to use
     * {@link Connection::quoteIdentifier()} as well.
     *
     * @param string|self|array $tables The table(s) to add to the FROM part. The items can be any mix of the
     *   following: ['table', 'table alias', 'alias' => 'table']
     *
     * @return $this
     */
    public function from(string|self|array $tables): static
    {
        if (! is_array($tables)) {
            $tables = [$tables];
        }

        $this->from = array_merge($this->from ?: [], $tables);

        return $this;
    }

    /**
     * Get the JOIN part(s) of the query
     *
     * @return ?array
     */
    public function getJoin(): ?array
    {
        return $this->join;
    }

    /**
     * Add an INNER JOIN part to the query
     *
     * @param string|self|array $table The table to be joined, can be any of the following:
     *   'table'  'table alias'  ['alias' => 'table']
     * @param string|ExpressionInterface|self|array $condition The join condition, i.e. the ON part of the JOIN.
     *   Please see {@link WhereInterface::where()} for the supported formats and restrictions regarding quoting of the
     *   field names.
     * @param string $operator The operator to combine multiple conditions with, if the condition is in the array format
     *
     * @return $this
     */
    public function join(
        string|self|array $table,
        string|ExpressionInterface|self|array $condition,
        string $operator = Sql::ALL
    ): static {
        $this->join[] = ['INNER', $table, $this->buildCondition($condition, $operator)];

        return $this;
    }

    /**
     * Add a LEFT JOIN part to the query
     *
     * @param string|self|array $table The table to be joined, can be any of the following:
     *   'table'  'table alias'  ['alias' => 'table']
     * @param string|ExpressionInterface|self|array $condition The join condition, i.e. the ON part of the JOIN.
     *   Please see {@link WhereInterface::where()} for the supported formats and restrictions regarding quoting of the
     *   field names.
     * @param string $operator The operator to combine multiple conditions with, if the condition is in the array format
     *
     * @return $this
     */
    public function joinLeft(
        string|self|array $table,
        string|ExpressionInterface|self|array $condition,
        string $operator = Sql::ALL
    ): static {
        $this->join[] = ['LEFT', $table, $this->buildCondition($condition, $operator)];

        return $this;
    }

    /**
     * Add a RIGHT JOIN part to the query
     *
     * @param string|self|array $table The table to be joined, can be any of the following:
     *   'table'  'table alias'  ['alias' => 'table']
     * @param string|ExpressionInterface|self|array $condition The join condition, i.e. the ON part of the JOIN.
     *   Please see {@link WhereInterface::where()} for the supported formats and restrictions regarding quoting of the
     *   field names.
     * @param string $operator The operator to combine multiple conditions with, if the condition is in the array format
     *
     * @return $this
     */
    public function joinRight(
        string|self|array $table,
        string|ExpressionInterface|self|array $condition,
        string $operator = Sql::ALL
    ): static {
        $this->join[] = ['RIGHT', $table, $this->buildCondition($condition, $operator)];

        return $this;
    }

    /**
     * Get the GROUP BY part of the query
     *
     * @return ?array
     */
    public function getGroupBy(): ?array
    {
        return $this->groupBy;
    }

    /**
     * Add a GROUP BY part to the query - either plain columns or expressions or scalar subqueries
     *
     * This method does NOT quote the columns you specify for the GROUP BY.
     * If you allow user input here, you must protect yourself against SQL injection using
     * {@link Connection::quoteIdentifier()} for the field names passed to this method.
     * If you are using special field names, e.g. reserved keywords for your DBMS, you are required to use
     * {@link Connection::quoteIdentifier()} as well.
     *
     * Note that this method does not override an already set GROUP BY part. Instead, multiple calls to this function
     * add the specified GROUP BY part.
     *
     * @param string|ExpressionInterface|self|array $groupBy
     *
     * @return $this
     */
    public function groupBy(string|ExpressionInterface|self|array $groupBy): static
    {
        $this->groupBy = array_merge(
            $this->groupBy ?? [],
            is_array($groupBy) ? $groupBy : [$groupBy]
        );

        return $this;
    }

    /**
     * Get the HAVING part of the query
     *
     * @return ?array
     */
    public function getHaving(): ?array
    {
        return $this->having;
    }

    /**
     * Add a HAVING part of the query
     *
     * This method lets you specify the HAVING part of the query using one of the two following supported formats:
     * * String format, e.g. 'id = 1'
     * * Array format, e.g. ['id' => 1, ...]
     *
     * This method does NOT quote the columns you specify for the HAVING.
     * If you allow user input here, you must protect yourself against SQL injection using
     * {@link Connection::quoteIdentifier()} for the field names passed to this method.
     * If you are using special field names, e.g. reserved keywords for your DBMS, you are required to use
     * {@link Connection::quoteIdentifier()} as well.
     *
     * Note that this method does not override an already set HAVING part. Instead, multiple calls to this function add
     * the specified HAVING part using the AND operator.
     *
     * @param string|ExpressionInterface|self|array $condition The HAVING condition
     * @param string $operator The operator to combine multiple conditions with,
     *   if the condition is in the array format
     *
     * @return $this
     */
    public function having(string|ExpressionInterface|self|array $condition, string $operator = Sql::ALL): static
    {
        $this->mergeCondition($this->having, $this->buildCondition($condition, $operator), Sql::ALL);

        return $this;
    }

    /**
     * Add an OR part to the HAVING part of the query
     *
     * Please see {@link having()} for the supported formats and restrictions regarding quoting of the field names.
     *
     * @param string|ExpressionInterface|self|array $condition The HAVING condition
     * @param string $operator The operator to combine multiple conditions with,
     *   if the condition is in the array format
     *
     * @return $this
     */
    public function orHaving(string|ExpressionInterface|self|array $condition, string $operator = Sql::ALL): static
    {
        $this->mergeCondition($this->having, $this->buildCondition($condition, $operator), Sql::ANY);

        return $this;
    }

    /**
     * Add a AND NOT part to the HAVING part of the query
     *
     * Please see {@link having()} for the supported formats and restrictions regarding quoting of the field names.
     *
     * @param string|ExpressionInterface|self|array $condition The HAVING condition
     * @param string $operator The operator to combine multiple conditions with,
     *   if the condition is in the array format
     *
     * @return $this
     */
    public function notHaving(string|ExpressionInterface|self|array $condition, string $operator = Sql::ALL): static
    {
        $this->mergeCondition($this->having, $this->buildCondition($condition, $operator), Sql::NOT_ALL);

        return $this;
    }

    /**
     * Add a OR NOT part to the HAVING part of the query
     *
     * Please see {@link having()} for the supported formats and restrictions regarding quoting of the field names.
     *
     * @param string|ExpressionInterface|self|array $condition The HAVING condition
     * @param string $operator The operator to combine multiple conditions with,
     *   if the condition is in the array format
     *
     * @return $this
     */
    public function orNotHaving(string|ExpressionInterface|self|array $condition, string $operator = Sql::ALL): static
    {
        $this->mergeCondition($this->having, $this->buildCondition($condition, $operator), Sql::NOT_ANY);

        return $this;
    }

    /**
     * Get the UNION parts of the query
     *
     * @return ?array
     */
    public function getUnion(): ?array
    {
        return $this->union;
    }

    /**
     * Combine a query with UNION
     *
     * @param string|self $query
     *
     * @return $this
     */
    public function union(string|self $query): static
    {
        $this->union[] = [$query, false];

        return $this;
    }

    /**
     * Combine a query with UNION ALL
     *
     * @param string|self $query
     *
     * @return $this
     */
    public function unionAll(string|self $query): static
    {
        $this->union[] = [$query, true];

        return $this;
    }

    /**
     * Reset the DISTINCT part of the query
     *
     * @return $this
     */
    public function resetDistinct(): static
    {
        $this->distinct = false;

        return $this;
    }

    /**
     * Reset the columns of the query
     *
     * @return $this
     */
    public function resetColumns(): static
    {
        $this->columns = null;

        return $this;
    }

    /**
     * Reset the FROM part of the query
     *
     * @return $this
     */
    public function resetFrom(): static
    {
        $this->from = null;

        return $this;
    }

    /**
     * Reset the JOIN parts of the query
     *
     * @return $this
     */
    public function resetJoin(): static
    {
        $this->join = null;

        return $this;
    }

    /**
     * Reset the GROUP BY part of the query
     *
     * @return $this
     */
    public function resetGroupBy(): static
    {
        $this->groupBy = null;

        return $this;
    }

    /**
     * Reset the HAVING part of the query
     *
     * @return $this
     */
    public function resetHaving(): static
    {
        $this->having = null;

        return $this;
    }

    /**
     * Reset queries combined with UNION and UNION ALL
     *
     * @return $this
     */
    public function resetUnion(): static
    {
        $this->union = null;

        return $this;
    }

    /**
     * Get the count query
     *
     * @return self
     */
    public function getCountQuery(): self
    {
        $countQuery = clone $this;

        $countQuery->orderBy = null;
        $countQuery->limit = null;
        $countQuery->offset = null;

        if (! empty($countQuery->groupBy) || $countQuery->getDistinct()) {
            $countQuery = (new self())->from(['s' => $countQuery]);
            $countQuery->distinct(false);
        }

        $countQuery->columns = ['cnt' => 'COUNT(*)'];

        return $countQuery;
    }

    public function __clone()
    {
        $this->cloneCte();
        $this->cloneOrderBy();
        $this->cloneWhere();

        if ($this->columns !== null) {
            foreach ($this->columns as &$value) {
                if ($value instanceof ExpressionInterface || $value instanceof self) {
                    $value = clone $value;
                }
            }
            unset($value);
        }

        if ($this->from !== null) {
            foreach ($this->from as &$from) {
                if ($from instanceof self) {
                    $from = clone $from;
                }
            }
            unset($from);
        }

        if ($this->join !== null) {
            foreach ($this->join as &$join) {
                if (is_array($join[1])) {
                    foreach ($join[1] as &$table) {
                        if ($table instanceof self) {
                            $table = clone $table;
                        }
                    }
                    unset($table);
                } elseif ($join[1] instanceof self) {
                    $join[1] = clone $join[1];
                }

                $this->cloneCondition($join[2]);
            }
            unset($join);
        }

        if ($this->groupBy !== null) {
            foreach ($this->groupBy as &$value) {
                if ($value instanceof ExpressionInterface || $value instanceof self) {
                    $value = clone $value;
                }
            }
            unset($value);
        }

        if ($this->having !== null) {
            $this->cloneCondition($this->having);
        }

        if ($this->union !== null) {
            foreach ($this->union as &$union) {
                $union[0] = clone $union[0];
            }
            unset($union);
        }
    }
}
