<?php

namespace ipl\Orm;

use Generator;
use ipl\Sql\Expression;
use ipl\Sql\ExpressionInterface;
use RuntimeException;

class ResolvedExpression extends Expression
{
    /** @var Generator */
    protected $resolvedColumns;

    /**
     * Create a resolved database expression
     *
     * @param ExpressionInterface $expr The original expression
     * @param Generator $resolvedColumns The generator as returned by {@see Resolver::requireAndResolveColumns()}
     */
    public function __construct(ExpressionInterface $expr, Generator $resolvedColumns)
    {
        parent::__construct($expr->getStatement(), $expr->getColumns(), ...$expr->getValues());

        $this->resolvedColumns = $resolvedColumns;
    }

    /**
     * @throws RuntimeException In case the columns are not qualified yet
     */
    public function getColumns()
    {
        if ($this->resolvedColumns->valid()) {
            throw new RuntimeException('Columns are not yet qualified');
        }

        return parent::getColumns();
    }

    /**
     * Get the resolved column generator
     *
     * @return Generator
     */
    public function getResolvedColumns()
    {
        return $this->resolvedColumns;
    }
}
