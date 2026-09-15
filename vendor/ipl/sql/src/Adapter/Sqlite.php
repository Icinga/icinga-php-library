<?php

namespace ipl\Sql\Adapter;

use ipl\Sql\Config;

class Sqlite extends BaseAdapter
{
    public function getDsn(Config $config): string
    {
        return "sqlite:{$config->dbname}";
    }
}
