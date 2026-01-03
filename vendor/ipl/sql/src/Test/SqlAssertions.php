<?php

namespace ipl\Sql\Test;

use ipl\Sql\Delete;
use ipl\Sql\Insert;
use ipl\Sql\QueryBuilder;
use ipl\Sql\Select;
use ipl\Sql\Update;

trait SqlAssertions
{
    /** @var string The adapter to use */
    protected $adapterClass = TestAdapter::class;

    /** @var QueryBuilder */
    protected $queryBuilder;

    abstract public function setUp(): void;

    protected function setUpSqlAssertions(): void
    {
        $this->queryBuilder = new QueryBuilder(new $this->adapterClass());
    }

    /**
     * Assert that the given statement equals the given SQL once assembled
     *
     * @param string $sql
     * @param Delete|Insert|Select|Update $statement
     * @param ?array $values
     * @param string $message
     *
     * @return void
     */
    public function assertSql(string $sql, $statement, array $values = null, string $message = ''): void
    {
        // Reduce whitespaces to just one space
        $sql = preg_replace('/\s+/', ' ', trim($sql));

        list($stmt, $bind) = $this->queryBuilder->assemble($statement);

        $this->assertSame($sql, $stmt, $message);

        if ($values !== null) {
            $this->assertSame($values, $bind, $message);
        }
    }
}
