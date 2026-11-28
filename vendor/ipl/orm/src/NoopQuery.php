<?php

namespace ipl\Orm;

use Generator;
use ipl\Sql\Connection;
use LogicException;
use RuntimeException;

/**
 * A noop query imitates a real query but never yields any result
 *
 * @internal Used exclusively by ipl/orm in places to not break an interface's contract.
 */
class NoopQuery extends Query
{
    public function getDb()
    {
        throw new RuntimeException('A noop query does not have database connection');
    }

    public function setDb(Connection $db): static
    {
        throw new LogicException('A noop query does not need a database connection');
    }

    public function yieldResults(): Generator
    {
        yield from [];
    }

    public function count(): int
    {
        return 0;
    }

    public function dump(): array
    {
        return ['noop', []];
    }

    public function derive($relation, Model $source): static
    {
        throw new LogicException('Cannot derive noop query');
    }

    public function createSubQuery(Model $target, string $targetPath, ?Model $from = null, bool $link = true): static
    {
        throw new LogicException('Cannot derive noop query');
    }
}
