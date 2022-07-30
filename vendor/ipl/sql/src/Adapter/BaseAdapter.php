<?php

namespace ipl\Sql\Adapter;

use DateTime;
use DateTimeZone;
use ipl\Sql\Config;
use ipl\Sql\Connection;
use ipl\Sql\Contract\Adapter;
use ipl\Sql\QueryBuilder;
use PDO;

abstract class BaseAdapter implements Adapter
{
    /**
     * Quote character to use for quoting identifiers
     *
     * The default quote character is the double quote (") which is used by databases that behave close to ANSI SQL.
     *
     * @var array
     */
    protected $quoteCharacter = ['"', '"'];

    /** @var string Character to use for escaping quote characters */
    protected $escapeCharacter = '\\"';

    /** @var array Default PDO connect options */
    protected $options = [
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false
    ];

    public function getDsn(Config $config)
    {
        $dsn = "{$config->db}:";

        $parts = [];

        foreach (['host', 'dbname', 'port'] as $part) {
            if (! empty($config->$part)) {
                $parts[] = "{$part}={$config->$part}";
            }
        }

        return $dsn . implode(';', $parts);
    }

    public function getOptions(Config $config)
    {
        if (is_array($config->options)) {
            return $config->options + $this->options;
        }

        return $this->options;
    }

    public function setClientTimezone(Connection $db)
    {
    }

    public function quoteIdentifier($identifiers)
    {
        if (is_string($identifiers)) {
            $identifiers = explode('.', $identifiers);
        }

        foreach ($identifiers as $i => $identifier) {
            if ($identifier === '*') {
                continue;
            }

            $identifiers[$i] = $this->quoteCharacter[0]
                . str_replace($this->quoteCharacter[0], $this->escapeCharacter, $identifier)
                . $this->quoteCharacter[1];
        }

        return implode('.', $identifiers);
    }

    public function registerQueryBuilderCallbacks(QueryBuilder $queryBuilder)
    {
    }

    protected function getTimezoneOffset()
    {
        $tz = new DateTimeZone(date_default_timezone_get());
        $offset = $tz->getOffset(new DateTime());
        $prefix = $offset >= 0 ? '+' : '-';
        $offset = abs($offset);

        $hours = (int) floor($offset / 3600);
        $minutes = (int) floor(($offset % 3600) / 60);

        return sprintf('%s%d:%02d', $prefix, $hours, $minutes);
    }
}
