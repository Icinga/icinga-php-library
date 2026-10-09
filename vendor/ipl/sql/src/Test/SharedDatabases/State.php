<?php

namespace ipl\Sql\Test\SharedDatabases;

use ipl\Sql\Config;
use ipl\Sql\Connection;
use ipl\Sql\Test\SharedDatabases;
use RuntimeException;

/**
 * Shared state for database connections used by the {@see SharedDatabases} trait.
 *
 * It's a final singleton because it's only used internally by the trait to ensure
 * a single connection for each supported driver. Otherwise tests cannot isolate
 * their cases in individual transactions.
 *
 * @internal Must only be used by the {@see SharedDatabases} trait.
 */
final class State
{
    /** @var string[] */
    private const SUPPORTED_ADAPTERS = ['mssql', 'mysql', 'oracle', 'pgsql', 'sqlite'];

    /** @var array<string, Connection> */
    private static array $connections = [];

    /** @var ?string ID to make schema groups unique per run */
    private static ?string $groupSalt = null;

    /**
     * Set up the database connections
     *
     * @return array<string, Connection[]>
     */
    public static function databases(): array
    {
        if (empty(self::$connections)) {
            foreach (self::SUPPORTED_ADAPTERS as $driver) {
                if (isset($_SERVER[strtoupper($driver) . '_TESTDB'])) {
                    self::$connections[$driver] = [self::connectToDatabase($driver)];
                }
            }
        }

        return self::$connections;
    }

    /**
     * Get a unique (per test run) identifier for the given group
     *
     * @param string $group
     *
     * @return string
     */
    public static function uniqueGroup(string $group): string
    {
        if (self::$groupSalt === null) {
            self::$groupSalt = uniqid();
        }

        return sha1($group . self::$groupSalt);
    }

    /**
     * Create a database connection
     *
     * @param string $driver
     *
     * @return Connection
     */
    private static function connectToDatabase(string $driver): Connection
    {
        return new Connection(self::getConnectionConfig($driver));
    }

    /**
     * Get the connection configuration for the test database
     *
     * @param string $driver
     *
     * @return Config
     */
    private static function getConnectionConfig(string $driver): Config
    {
        return new Config([
            'db' => $driver,
            'host' => self::getEnvironmentVariable(strtoupper($driver) . '_TESTDB_HOST'),
            'port' => self::getEnvironmentVariable(strtoupper($driver) . '_TESTDB_PORT'),
            'username' => self::getEnvironmentVariable(strtoupper($driver) . '_TESTDB_USER'),
            'password' => self::getEnvironmentVariable(strtoupper($driver) . '_TESTDB_PASSWORD'),
            'dbname' => self::getEnvironmentVariable(strtoupper($driver) . '_TESTDB')
        ]);
    }

    /**
     * Get the value of an environment variable
     *
     * @param string $name
     *
     * @return string
     *
     * @throws RuntimeException if the environment variable is not set
     */
    private static function getEnvironmentVariable(string $name): string
    {
        $value = getenv($name);
        if ($value === false) {
            throw new RuntimeException("Environment variable $name is not set");
        }

        return $value;
    }
}
