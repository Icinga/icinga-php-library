<?php

namespace ipl\Sql\Adapter;

use ipl\Sql\Config;
use ipl\Sql\Connection;

class Oracle extends BaseAdapter
{
    public function getDsn(Config $config): string
    {
        $dsn = 'oci:dbname=';

        if (! empty($config->host)) {
            $dsn .= "//{$config->host}";

            if (! empty($config->port)) {
                $dsn .= ":{$config->port}/";
            }

            $dsn .= '/';
        }

        $dsn .= $config->dbname;

        if (! empty($config->charset)) {
            $dsn .= ";charset={$config->charset}";
        }

        return $dsn;
    }

    public function setClientTimezone(Connection $db): static
    {
        $db->prepexec('ALTER SESSION SET TIME_ZONE = ?', [$this->getTimezoneOffset()]);

        return $this;
    }
}
