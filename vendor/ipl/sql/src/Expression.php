<?php

namespace ipl\Sql;

/**
 * A database expression that does need quoting or escaping, e.g. new Expression('NOW()');
 */
class Expression implements ExpressionInterface
{
    /** @var string The statement of the expression */
    protected string $statement;

    /** @var ?array The columns used by the expression */
    protected ?array $columns;

    /** @var array The values for the expression */
    protected array $values;

    /**
     * Create a new database expression
     *
     * @param string $statement The statement of the expression
     * @param ?array $columns The columns used by the expression
     * @param mixed  ...$values The values for the expression
     */
    public function __construct(string $statement, ?array $columns = null, ...$values)
    {
        $this->statement = $statement;
        $this->columns = $columns;
        $this->values = $values;
    }

    public function getStatement(): string
    {
        return $this->statement;
    }

    public function getColumns(): array
    {
        return $this->columns ?: [];
    }

    public function setColumns(array $columns): static
    {
        $this->columns = $columns;

        return $this;
    }

    public function getValues(): array
    {
        return $this->values;
    }
}
