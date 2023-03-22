<?php

namespace ipl\Sql;

use InvalidArgumentException;
use ipl\Sql\Adapter\Mssql;
use ipl\Sql\Contract\Adapter;
use ipl\Stdlib\Events;

use function ipl\Stdlib\get_php_type;

class QueryBuilder
{
    use Events;

    /**
     * Event raised when a {@link Select} object is assembled into a SQL statement string
     *
     * The {@link Select} object is passed as parameter to the event callbacks.
     *
     * **Example usage:**
     *
     * ```
     * $queryBuilder->on(QueryBuilder::ON_ASSEMBLE_SELECT, function (Select $select) {
     *     // ...
     * });
     * ```
     */
    const ON_ASSEMBLE_SELECT = 'assembleSelect';

    /**
     * Event raised after a {@link Select} object is assembled into a SQL statement string
     *
     * The assembled SQL statement string and the values to bind to the statement are passed as parameters by reference
     * to the event callbacks.
     *
     * **Example usage:**
     *
     * ```
     * $queryBuilder->on(QueryBuilder::ON_SELECT_ASSEMBLED, function (&$sql, &$values) {
     *     // ...
     * });
     * ```
     */
    const ON_SELECT_ASSEMBLED = 'selectAssembled';

    /**
     * Event raised before an {@see Insert} object is assembled into a SQL statement string
     *
     * **Example usage:**
     *
     * ```
     * $queryBuilder->on(QueryBuilder::ON_ASSEMBLE_INSERT, function (Insert $insert) {
     *     // ...
     * });
     * ```
     *
     * @var string
     */
    const ON_ASSEMBLE_INSERT = 'assembleInsert';

    /**
     * Event raised after an {@see Insert} object is assembled into a SQL statement string
     *
     * The assembled SQL statement string and the prepared values are passed by reference to the event callbacks
     *
     * **Example usage:**
     *
     * ```
     * $queryBuilder->on(QueryBuilder::ON_INSERT_ASSEMBLED, function (&$sql, &$values) {
     *     // ...
     * });
     * ```
     *
     * @var string
     */
    const ON_INSERT_ASSEMBLED = 'insertAssembled';

    /**
     * Event raised before an {@see Update} object is assembled into a SQL statement string
     *
     * **Example usage:**
     *
     * ```
     * $queryBuilder->on(QueryBuilder::ON_ASSEMBLE_UPDATE, function (Update $update) {
     *     // ...
     * });
     * ```
     *
     * @var string
     */
    const ON_ASSEMBLE_UPDATE = 'assembleUpdate';

    /**
     * Event raised after an {@see Update} object is assembled into a SQL statement string
     *
     * The assembled SQL statement string and the prepared values are passed by reference to the event callbacks
     *
     * **Example usage:**
     *
     * ```
     * $queryBuilder->on(QueryBuilder::ON_UPDATE_ASSEMBLED, function (&$sql, &$values) {
     *     // ...
     * });
     * ```
     *
     * @var string
     */
    const ON_UPDATE_ASSEMBLED = 'updateAssembled';

    /**
     * Event raised before a {@see Delete} object is assembled into a SQL statement string
     *
     * **Example usage:**
     *
     * ```
     * $queryBuilder->on(QueryBuilder::ON_ASSEMBLE_DELETE, function (Delete $delete) {
     *     // ...
     * });
     * ```
     *
     * @var string
     */
    const ON_ASSEMBLE_DELETE = 'assembleDelete';

    /**
     * Event raised after a {@see Delete} object is assembled into a SQL statement string
     *
     * The assembled SQL statement string and the prepared values are passed by reference to the event callbacks
     *
     * **Example usage:**
     *
     * ```
     * $queryBuilder->on(QueryBuilder::ON_DELETE_ASSEMBLED, function (&$sql, &$values) {
     *     // ...
     * });
     * ```
     *
     * @var string
     */
    const ON_DELETE_ASSEMBLED = 'deleteAssembled';

    /** @var Adapter */
    protected $adapter;

    protected $separator = " ";

    /**
     * Create a new query builder for the specified database adapter
     *
     * @param Adapter $adapter
     */
    public function __construct(Adapter $adapter)
    {
        $adapter->registerQueryBuilderCallbacks($this);

        $this->adapter = $adapter;
    }

    /**
     * Assemble the given statement
     *
     * @param Delete|Insert|Select|Update $stmt
     *
     * @return array
     *
     * @throw InvalidArgumentException If statement type is invalid
     */
    public function assemble($stmt)
    {
        switch (true) {
            case $stmt instanceof Delete:
                return $this->assembleDelete($stmt);
            case $stmt instanceof Insert:
                return $this->assembleInsert($stmt);
            case $stmt instanceof Select:
                return $this->assembleSelect($stmt);
            case $stmt instanceof Update:
                return $this->assembleUpdate($stmt);
            default:
                throw new InvalidArgumentException(sprintf(
                    __METHOD__ . ' expects instances of Delete, Insert, Select or Update. Got %s instead.',
                    get_php_type($stmt)
                ));
        }
    }

    /**
     * Assemble a DELETE query
     *
     * @param Delete $delete
     *
     * @return array
     */
    public function assembleDelete(Delete $delete)
    {
        $values = [];

        $this->emit(self::ON_ASSEMBLE_DELETE, [$delete]);

        $sql = array_filter([
            $this->buildWith($delete->getWith(), $values),
            $this->buildDeleteFrom($delete->getFrom()),
            $this->buildWhere($delete->getWhere(), $values)
        ]);

        $sql = implode($this->separator, $sql);

        $this->emit(static::ON_DELETE_ASSEMBLED, [&$sql, &$values]);

        return [$sql, $values];
    }

    /**
     * Assemble a INSERT statement
     *
     * @param Insert $insert
     *
     * @return array
     */
    public function assembleInsert(Insert $insert)
    {
        $values = [];

        $this->emit(static::ON_ASSEMBLE_INSERT, [$insert]);

        $select = $insert->getSelect();

        $sql = array_filter([
            $this->buildWith($insert->getWith(), $values),
            $this->buildInsertInto($insert->getInto()),
            $select
                ? $this->buildInsertIntoSelect($insert->getColumns(), $select, $values)
                : $this->buildInsertColumnsAndValues($insert->getColumns(), $insert->getValues(), $values)
        ]);

        $sql = implode($this->separator, $sql);

        $this->emit(static::ON_INSERT_ASSEMBLED, [&$sql, &$values]);

        return [$sql, $values];
    }

    /**
     * Assemble a SELECT query
     *
     * @param Select $select
     * @param array  $values
     *
     * @return array
     */
    public function assembleSelect(Select $select, array &$values = [])
    {
        $select = clone $select;

        $this->emit(static::ON_ASSEMBLE_SELECT, [$select]);

        $sql = array_filter([
            $this->buildWith($select->getWith(), $values),
            $this->buildSelect($select->getColumns(), $select->getDistinct(), $values),
            $this->buildFrom($select->getFrom(), $values),
            $this->buildJoin($select->getJoin(), $values),
            $this->buildWhere($select->getWhere(), $values),
            $this->buildGroupBy($select->getGroupBy(), $values),
            $this->buildHaving($select->getHaving(), $values),
            $this->buildOrderBy($select->getOrderBy(), $values),
            $this->buildLimitOffset($select->getLimit(), $select->getOffset())
        ]);

        $sql = implode($this->separator, $sql);

        $unions = $this->buildUnions($select->getUnion(), $values);
        if ($unions) {
            list($unionKeywords, $selects) = $unions;

            if ($sql) {
                $sql = "($sql)";

                $requiresUnionKeyword = true;
            } else {
                $requiresUnionKeyword = false;
            }

            do {
                $unionKeyword = array_shift($unionKeywords);
                $select = array_shift($selects);

                if ($requiresUnionKeyword) {
                    $sql .= "{$this->separator}$unionKeyword{$this->separator}";
                }

                $sql .= "($select)";

                $requiresUnionKeyword = true;
            } while (! empty($unionKeywords));
        }

        $this->emit(static::ON_SELECT_ASSEMBLED, [&$sql, &$values]);

        return [$sql, $values];
    }

    /**
     * Assemble a UPDATE query
     *
     * @param Update $update
     *
     * @return array
     */
    public function assembleUpdate(Update $update)
    {
        $values = [];

        $this->emit(self::ON_ASSEMBLE_UPDATE, [$update]);

        $sql = array_filter([
            $this->buildWith($update->getWith(), $values),
            $this->buildUpdateTable($update->getTable()),
            $this->buildUpdateSet($update->getSet(), $values),
            $this->buildWhere($update->getWhere(), $values)
        ]);

        $sql = implode($this->separator, $sql);

        $this->emit(static::ON_UPDATE_ASSEMBLED, [&$sql, &$values]);

        return [$sql, $values];
    }

    /**
     * Build the WITH part of a query
     *
     * @param array $with
     * @param array $values
     *
     * @return string The WITH part of a query
     */
    public function buildWith(array $with, array &$values)
    {
        if (empty($with)) {
            return '';
        }

        $ctes = [];
        $hasRecursive = false;

        foreach ($with as $cte) {
            list($query, $alias, $recursive) = $cte;
            list($cteSql, $cteValues) = $this->assembleSelect($query);

            $ctes[] = "$alias AS ($cteSql)";

            $values = array_merge($values, $cteValues);
            $hasRecursive |= $recursive;
        }

        return ($hasRecursive ? 'WITH RECURSIVE ' : 'WITH ') . implode(', ', $ctes);
    }

    /**
     * Build the DELETE FROM part of a query
     *
     * @param array $from
     *
     * @return string The DELETE FROM part of a query
     */
    public function buildDeleteFrom(array $from = null)
    {
        if ($from === null) {
            return '';
        }

        $deleteFrom = 'DELETE FROM';

        reset($from);
        $alias = key($from);
        $table = current($from);

        if (is_int($alias)) {
            $deleteFrom .= " $table";
        } else {
            $deleteFrom .= " $table $alias";
        }

        return $deleteFrom;
    }

    /**
     * Outsourced logic of {@link buildCondition()}
     *
     * @param string $expression
     * @param array  $values
     *
     * @return array
     */
    public function unpackCondition($expression, array $values)
    {
        $placeholders = preg_match_all('/(\?)/', $expression, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

        if ($placeholders === 0) {
            return [$expression, []];
        }

        if ($placeholders === 1) {
            $offset = $matches[0][1][1];
            $expression = substr($expression, 0, $offset)
                . implode(', ', array_fill(0, count($values), '?'))
                . substr($expression, $offset + 1);

            return [$expression, $values];
        }

        $unpackedExpression = [];
        $unpackedValues = [];
        $offset = 0;

        foreach ($matches as $match) {
            $value = array_shift($values);
            $unpackedExpression[] = substr($expression, $offset, $match[1][1] - $offset);
            if (is_array($value)) {
                $unpackedExpression[] = implode(', ', array_fill(0, count($value), '?'));
                $unpackedValues = array_merge($unpackedValues, $value);
            } else {
                $unpackedExpression[] = '?';
                $unpackedValues[] = $value;
            }
            $offset = $match[1][1] + 1; // 1 is the length of '?'
        }

        $unpackedExpression[] = substr($expression, $offset);

        return [implode('', array_filter($unpackedExpression)), $unpackedValues];
    }

    /**
     * Outsourced logic {@link buildWhere()} and {@link buildHaving()} have in common
     *
     * @param array $condition
     * @param array $values
     *
     * @return string
     */
    public function buildCondition(array $condition, array &$values)
    {
        $sql = [];

        $operator = array_shift($condition);
        $conditions = array_shift($condition);

        foreach ($conditions as $expression => $value) {
            if (is_array($value)) {
                if (is_int($expression)) {
                    // Operator format
                    $sql[] = $this->buildCondition($value, $values);
                } else {
                    list($unpackedExpression, $unpackedValues) = $this->unpackCondition($expression, $value);
                    $sql[] = $unpackedExpression;
                    $values = array_merge($values, $unpackedValues);
                }
            } else {
                if ($value instanceof ExpressionInterface) {
                    $sql[] = $this->buildExpression($value, $values);
                } elseif ($value instanceof Select) {
                    $stmt = '(' . $this->assembleSelect($value, $values)[0] . ')';
                    if (is_int($expression)) {
                        $sql[] = $stmt;
                    } else {
                        $sql[] = str_replace('?', $stmt, $expression);
                    }
                } elseif (is_int($expression)) {
                    $sql[] = $value;
                } else {
                    $sql[] = $expression;
                    $values[] = $value;
                }
            }
        }

        if ($operator === Sql::NOT_ALL || $operator === Sql::NOT_ANY) {
            return 'NOT (' . implode(") $operator (", $sql) . ')';
        }

        return count($sql) === 1 ? $sql[0] : '(' . implode(") $operator (", $sql) . ')';
    }

    /**
     * Build the WHERE part of a query
     *
     * @param array $where
     * @oaram array $values
     *
     * @return string The WHERE part of the query
     */
    public function buildWhere(array $where = null, array &$values = [])
    {
        if ($where === null) {
            return '';
        }

        return 'WHERE ' . $this->buildCondition($where, $values);
    }

    /**
     * Build the INSERT INTO part of a INSERT INTO ... statement
     *
     * @param string|null $into
     *
     * @return string The INSERT INTO part of a INSERT INTO ... statement
     */
    public function buildInsertInto($into)
    {
        if (empty($into)) {
            return '';
        }

        return "INSERT INTO $into";
    }

    /**
     * Build the columns and SELECT part of a INSERT INTO ... SELECT statement
     *
     * @param array  $columns
     * @param Select $select
     * @param array  $values
     *
     * @return string The columns and SELECT part of the INSERT INTO ... SELECT statement
     */
    public function buildInsertIntoSelect(array $columns, Select $select, array &$values)
    {
        $sql = [
            '(' . implode(',', $columns) . ')',
            $this->assembleSelect($select, $values)[0]
        ];

        return implode($this->separator, $sql);
    }

    /**
     * Build the columns and values part of a INSERT INTO ... statement
     *
     * @param array $columns
     * @param array $insertValues
     * @param array $values
     *
     * @return string The columns and values part of a INSERT INTO ... statement
     */
    public function buildInsertColumnsAndValues(array $columns, array $insertValues, array &$values)
    {
        $sql = ['(' . implode(',', $columns) . ')'];

        $preparedValues = [];

        foreach ($insertValues as $value) {
            if ($value instanceof ExpressionInterface) {
                $preparedValues[] = $this->buildExpression($value, $values);
            } elseif ($value instanceof Select) {
                $preparedValues[] = "({$this->assembleSelect($value, $values)[0]})";
            } else {
                $preparedValues[] = '?';
                $values[] = $value;
            }
        }

        $sql[] = 'VALUES(' . implode(',', $preparedValues) . ')';

        return implode($this->separator, $sql);
    }

    /**
     * Build the SELECT part of a query
     *
     * @param array $columns
     * @param bool  $distinct
     * @param array $values
     *
     * @return string The SELECT part of the query
     */
    public function buildSelect(array $columns, $distinct, array &$values)
    {
        if (empty($columns)) {
            return '';
        }

        $select = 'SELECT';

        if ($distinct) {
            $select .= ' DISTINCT';
        }

        if (empty($columns)) {
            return "$select *";
        }

        $sql = [];

        foreach ($columns as $alias => $column) {
            if ($column instanceof ExpressionInterface) {
                $column = "({$this->buildExpression($column, $values)})";
            } elseif ($column instanceof Select) {
                $column = "({$this->assembleSelect($column, $values)[0]})";
            }

            if (is_int($alias)) {
                $sql[] = $column;
            } else {
                $sql[] = "$column AS $alias";
            }
        }

        return "$select " . implode(', ', $sql);
    }

    /**
     * Build the FROM part of a query
     *
     * @param array $from
     * @param array $values
     *
     * @return string The FROM part of the query
     */
    public function buildFrom(array $from = null, array &$values = [])
    {
        if ($from === null) {
            return '';
        }

        $sql = [];

        foreach ($from as $alias => $table) {
            if ($table instanceof Select) {
                $table = "({$this->assembleSelect($table, $values)[0]})";
            }

            if (is_int($alias) || $alias === $table) {
                $sql[] = $table;
            } else {
                $sql[] = "$table $alias";
            }
        }

        return 'FROM ' . implode(', ', $sql);
    }

    /**
     * Build the JOIN part(s) of a query
     *
     * @param array $joins
     * @oaram array $values
     *
     * @return string The JOIN part(s) of the query
     */
    public function buildJoin($joins, array &$values)
    {
        if ($joins === null) {
            return '';
        }

        $sql = [];
        foreach ($joins as $join) {
            list($joinType, $table, $condition) = $join;

            if (is_array($table)) {
                foreach ($table as $alias => $tableName) {
                    break;
                }
            } else {
                $alias = null;
                $tableName = $table;
            }

            if ($tableName instanceof Select) {
                $tableName = "({$this->assembleSelect($tableName, $values)[0]})";
            }

            if (is_array($condition)) {
                $condition = $this->buildCondition($condition, $values);
            }

            if (empty($alias) || $alias === $tableName) {
                $sql[] = "$joinType JOIN $tableName ON $condition";
            } else {
                $sql[] = "$joinType JOIN $tableName $alias ON $condition";
            }
        }

        return implode($this->separator, $sql);
    }

    /**
     * Build the GROUP BY part of a query
     *
     * @param array $groupBy
     * @param array $values
     *
     * @return string The GROUP BY part of the query
     */
    public function buildGroupBy(array $groupBy = null, array &$values = [])
    {
        if ($groupBy === null) {
            return '';
        }

        foreach ($groupBy as &$column) {
            if ($column instanceof ExpressionInterface) {
                $column = $this->buildExpression($column, $values);
            } elseif ($column instanceof Select) {
                $column = "({$this->assembleSelect($column, $values)[0]})";
            }
        }

        return 'GROUP BY ' . implode(', ', $groupBy);
    }

    /**
     * Build the HAVING part of a query
     *
     * @param array $having
     * @param array $values
     *
     * @return string The HAVING part of the query
     */
    public function buildHaving(array $having = null, array &$values = [])
    {
        if ($having === null) {
            return '';
        }

        return 'HAVING ' . $this->buildCondition($having, $values);
    }

    /**
     * Build the ORDER BY part of a query
     *
     * @param array $orderBy
     * @param array $values
     *
     * @return string The ORDER BY part of the query
     */
    public function buildOrderBy(array $orderBy = null, array &$values = [])
    {
        if ($orderBy === null) {
            return '';
        }

        $sql = [];

        foreach ($orderBy as $column) {
            list($column, $direction) = $column;

            if ($column instanceof ExpressionInterface) {
                $column = $this->buildExpression($column, $values);
            } elseif ($column instanceof Select) {
                $column = "({$this->assembleSelect($column, $values)[0]})";
            }

            if ($direction !== null) {
                $sql[] = "$column $direction";
            } else {
                $sql[] = $column;
            }
        }

        return 'ORDER BY ' . implode(', ', $sql);
    }

    /**
     * Build the LIMIT and OFFSET part of a query
     *
     * @param int $limit
     * @param int $offset
     *
     * @return string The LIMIT and OFFSET part of the query
     */
    public function buildLimitOffset($limit = null, $offset = null)
    {
        $sql = [];

        if ($this->adapter instanceof Mssql) {
            if ($offset !== null || $limit !== null) {
                // If offset is null, sprintf will convert it to 0
                $sql[] = sprintf('OFFSET %d ROWS', $offset);
            }

            if ($limit !== null) {
                // FETCH FIRST n ROWS ONLY for OFFSET 0 would be an alternative here
                $sql[] = "FETCH NEXT $limit ROWS ONLY";
            }
        } else {
            if ($limit !== null) {
                $sql[] = "LIMIT $limit";
            }

            if ($offset !== null) {
                $sql[] = "OFFSET $offset";
            }
        }

        return implode($this->separator, $sql);
    }

    /**
     * Build the UNION parts of a query
     *
     * @param array $unions
     * @param array $values
     *
     * @return array|null The UNION parts of the query
     */
    public function buildUnions(array $unions = null, array &$values = [])
    {
        if ($unions === null) {
            return null;
        }

        $unionKeywords = [];
        $selects = [];

        foreach ($unions as $union) {
            list($select, $all) = $union;

            if ($select instanceof Select) {
                list($select, $values) = $this->assembleSelect($select, $values);
            }

            $unionKeywords[] = ($all ? 'UNION ALL' : 'UNION');
            $selects[] = $select;
        }

        return [$unionKeywords, $selects];
    }

    /**
     * Build the UPDATE {table} part of a query
     *
     * @param array $updateTable The table to UPDATE
     *
     * @return string The UPDATE {table} part of the query
     */
    public function buildUpdateTable(array $updateTable = null)
    {
        if ($updateTable === null) {
            return '';
        }

        $update = 'UPDATE';

        reset($updateTable);
        $alias = key($updateTable);
        $table = current($updateTable);

        if (is_int($alias)) {
            $update .= " $table";
        } else {
            $update .= " $table $alias";
        }

        return $update;
    }

    /**
     * Build the SET part of a UPDATE query
     *
     * @param array $set
     * @param array $values
     *
     * @return string The SET part of a UPDATE query
     */
    public function buildUpdateSet(array $set = null, array &$values = [])
    {
        if (empty($set)) {
            return '';
        }

        $sql = [];

        foreach ($set as $column => $value) {
            if ($value instanceof ExpressionInterface) {
                $sql[] = "$column = {$this->buildExpression($value, $values)}";
            } elseif ($value instanceof Select) {
                $sql[] = "$column = ({$this->assembleSelect($value, $values)[0]})";
            } else {
                $sql[] = "$column = ?";
                $values[] = $value;
            }
        }

        return 'SET ' . implode(', ', $sql);
    }

    /**
     * Build expression
     *
     * @param ExpressionInterface $expression
     * @param array $values
     *
     * @return string The expression's statement
     */
    public function buildExpression(ExpressionInterface $expression, array &$values = [])
    {
        $stmt = $expression->getStatement();
        $columns = $expression->getColumns();
        if (! empty($columns)) {
            $stmt = vsprintf($stmt, $columns);
        }

        $values = array_merge($values, $expression->getValues());

        return $stmt;
    }
}
