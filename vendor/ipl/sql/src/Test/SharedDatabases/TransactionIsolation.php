<?php

namespace ipl\Sql\Test\SharedDatabases;

use Attribute;
use ipl\Sql\Test\SharedDatabases;

/**
 * A test case using the {@see SharedDatabases} trait can be marked with this attribute to isolate
 * each test in its own transaction.
 */
#[Attribute(flags: Attribute::TARGET_CLASS)]
final class TransactionIsolation
{
}
