<?php

namespace ipl\Sql\Test;

use ipl\Sql\Select;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    use SqlAssertions;

    /** @var string The statement to use */
    protected string $queryClass = Select::class;

    /** @var Select The statement in use */
    protected $query;

    public function setUp(): void
    {
        $this->query = new $this->queryClass();
        $this->setUpSqlAssertions();
    }
}
