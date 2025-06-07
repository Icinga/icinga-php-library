<?php

namespace ipl\Orm;

use ipl\Sql\Expression;

class AliasedExpression extends Expression
{
    /** @var string */
    protected $alias;

    /**
     * Create a new database expression
     *
     * @param string $alias     The alias to use for the expression, this is expected to be quoted and qualified
     * @param string $statement The statement of the expression
     * @param ?array $columns   The columns used by the expression
     * @param mixed ...$values  The values for the expression
     */
    public function __construct(string $alias, string $statement, array $columns = null, ...$values)
    {
        parent::__construct($statement, $columns, ...$values);

        $this->alias = $alias;
    }

    /**
     * Get this expression's alias
     *
     * @return string
     */
    public function getAlias(): string
    {
        return $this->alias;
    }
}
