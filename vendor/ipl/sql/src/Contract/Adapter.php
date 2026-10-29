<?php

namespace ipl\Sql\Contract;

use ipl\Sql\Config;
use ipl\Sql\Connection;
use ipl\Sql\QueryBuilder;

interface Adapter extends Quoter
{
    /**
     * Get the DSN string from the given connection configuration
     *
     * @param Config $config
     *
     * @return string
     */
    public function getDsn(Config $config): string;

    /**
     * Get the PDO connect options based on the specified connection configuration
     *
     * @param Config $config
     *
     * @return array
     */
    public function getOptions(Config $config): array;

    /**
     * Set the client time zone
     *
     * @param Connection $db
     *
     * @return $this
     */
    public function setClientTimezone(Connection $db): static;

    /**
     * Register callbacks for query builder events
     *
     * @param QueryBuilder $queryBuilder
     *
     * @return $this
     */
    public function registerQueryBuilderCallbacks(QueryBuilder $queryBuilder): static;
}
