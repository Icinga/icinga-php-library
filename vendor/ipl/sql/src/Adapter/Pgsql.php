<?php

namespace ipl\Sql\Adapter;

use ipl\Sql\Connection;

class Pgsql extends BaseAdapter
{
    public function setClientTimezone(Connection $db)
    {
        $db->exec(sprintf('SET TIME ZONE INTERVAL %s HOUR TO MINUTE', $db->quote($this->getTimezoneOffset())));

        return $this;
    }
}
