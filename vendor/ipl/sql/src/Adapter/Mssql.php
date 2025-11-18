<?php

namespace ipl\Sql\Adapter;

use ipl\Sql\Config;
use ipl\Sql\QueryBuilder;
use ipl\Sql\Select;
use PDO;
use RuntimeException;

class Mssql extends BaseAdapter
{
    protected $quoteCharacter = ['[', ']'];

    protected $escapeCharacter = '[[]';

    public function getDsn(Config $config)
    {
        $drivers = array_intersect(['sqlsrv', 'dblib', 'mssql', 'sybase'], PDO::getAvailableDrivers());

        if (empty($drivers)) {
            throw new RuntimeException('No PDO driver available for connecting to a Microsoft SQL Server');
        }

        $driver = reset($drivers); // array_intersect preserves keys, so the first may not be indexed at 0

        $isSqlSrv = $driver === 'sqlsrv';
        if ($isSqlSrv) {
            $hostOption = 'Server';
            $dbOption = 'Database';
        } else {
            $hostOption = 'host';
            $dbOption = 'dbname';
        }

        $dsn = "{$driver}:{$hostOption}={$config->host}";

        if (! empty($config->port)) {
            if ($isSqlSrv || strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $seperator = ',';
            } else {
                $seperator = ':';
            }

            $dsn .= "{$seperator}{$config->port}";
        }

        $dsn .= ";{$dbOption}={$config->dbname}";

        if (! empty($config->charset) && ! $isSqlSrv) {
            $dsn .= ";charset={$config->charset}";
        }

        if (isset($config->useSsl) && $isSqlSrv) {
            $dsn .= ';Encrypt=' . ($config->useSsl ? 'true' : 'false');
        }

        if (isset($config->sslDoNotVerifyServerCert) && $isSqlSrv) {
            $dsn .= ';TrustServerCertificate=' . ($config->sslDoNotVerifyServerCert ? 'true' : 'false');
        }

        return $dsn;
    }

    public function registerQueryBuilderCallbacks(QueryBuilder $queryBuilder)
    {
        parent::registerQueryBuilderCallbacks($queryBuilder);

        $queryBuilder->on(QueryBuilder::ON_ASSEMBLE_SELECT, function (Select $select) {
            if (
                ($select->hasLimit() || $select->hasOffset())
                && ! $select->hasOrderBy()
            ) {
                $select->orderBy(1);
            }
        });

        return $this;
    }
}
