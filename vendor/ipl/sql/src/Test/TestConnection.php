<?php

namespace ipl\Sql\Test;

use ipl\Sql\Connection;

/**
 * Config-less test connection
 */
class TestConnection extends Connection
{
    public function __construct()
    {
        $this->adapter = new TestAdapter();
    }
}
