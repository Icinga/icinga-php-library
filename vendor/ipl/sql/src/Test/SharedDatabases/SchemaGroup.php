<?php

namespace ipl\Sql\Test\SharedDatabases;

use Attribute;
use ipl\Sql\Test\SharedDatabases;

/**
 * A test case using the trait {@see SharedDatabases} can be associated to a group using this attribute
 * in order to share the database schema between all test cases of the same group. By default, each class
 * using the trait will re-create the schema upon its run.
 */
#[Attribute(flags: Attribute::TARGET_CLASS)]
final readonly class SchemaGroup
{
    public function __construct(
        public string $name
    ) {
    }
}
