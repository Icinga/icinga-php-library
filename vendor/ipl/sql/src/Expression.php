<?php

namespace ipl\Sql;

/**
 * A database expression that does need quoting or escaping, e.g. new Expression('NOW()');
 */
class Expression implements ExpressionInterface
{
    /** @var string The statement of the expression */
    protected $statement;

    /** @var array The columns used by the expression */
    protected $columns;

    /** @var array The values for the expression */
    protected $values;

    /**
     * Create a new database expression
     *
     * @param string $statement The statement of the expression
     * @param array $columns The columns used by the expression
     * @param mixed  ...$values The values for the expression
     */
    public function __construct($statement, array $columns = null, ...$values)
    {
        $this->statement = $statement;
        $this->columns = $columns;
        $this->values = $values;
    }

    public function getStatement()
    {
        return $this->statement;
    }

    public function getColumns()
    {
        return $this->columns ?: [];
    }

    public function setColumns(array $columns)
    {
        $this->columns = $columns;

        return $this;
    }

    public function getValues()
    {
        return $this->values;
    }
}
