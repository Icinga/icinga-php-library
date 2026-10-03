<?php

namespace ipl\Sql\Test;

use ipl\Sql\Config;
use ipl\Sql\Connection;
use RuntimeException;

/**
 * Data provider for database connections. Use this to provide real database connections for your tests.
 *
 * To use it, implement {@see Databases::setUpSchema()} and {@see Databases::tearDownSchema()}.
 * The environment also needs to provide the following variables: (Replace * with the name of a supported adapter)
 * {@see Databases::SUPPORTED_ADAPTERS}
 *
 * Name              | Description
 * ----------------- | ------------------------
 * *_TESTDB          | The database to use
 * *_TESTDB_HOST     | The server to connect to
 * *_TESTDB_PORT     | The port to connect to
 * *_TESTDB_USER     | The user to connect with
 * *_TESTDB_PASSWORD | The password of the user
 *
 * Each test case will run multiple times, once for each database.
 * The connection is passed as the first argument to it.
 *
 * A schema will be initialized once the first test case using this provider is run. Schemas will first be dropped
 * and then recreated to ensure a clean state. During the entire test run, the same schema will be used for all
 * tests.
 *
 * If you need to implement your own setUp() and tearDown() methods, and need access to the database connection,
 * use {@see Databases::getConnection()}.
 */
trait SharedDatabases
{
    /**
     * All database connections
     *
     * @internal Only the trait itself should access this property
     *
     * @var array
     */
    private static array $connections = [];

    /** @var string[] */
    private const SUPPORTED_ADAPTERS = ['mssql', 'mysql', 'oracle', 'pgsql', 'sqlite'];

    /**
     * Create the schema for the test database
     *
     * @param Connection $db
     * @param string $driver
     *
     * @return void
     */
    abstract protected static function setUpSchema(Connection $db, string $driver): void;

    /**
     * Drop the schema of the test database
     *
     * @param Connection $db
     * @param string $driver
     *
     * @return void
     */
    abstract protected static function tearDownSchema(Connection $db, string $driver): void;

    /**
     * Provide the database connections
     *
     * @return array<string, Connection[]>
     */
    final public static function sharedDatabases(): array
    {
        self::initializeDatabases();

        return self::$connections;
    }

    /**
     * Get the current database connection
     *
     * @return Connection
     * @throws RuntimeException if the connection cannot be retrieved
     */
    final protected function getConnection(): Connection
    {
        if (method_exists($this, 'getProvidedData')) {
            $connections = $this->getProvidedData();
        } elseif (method_exists($this, 'providedData')) {
            $connections = $this->providedData();
        } else {
            throw new RuntimeException('Cannot get connection: Unsupported PHPUnit version?');
        }

        $connection = $connections[0];
        if (! $connection instanceof Connection) {
            throw new RuntimeException('Cannot get connection: Are all test cases using the same provider?');
        }

        return $connection;
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
    final protected static function getEnvironmentVariable(string $name): string
    {
        $value = getenv($name);
        if ($value === false) {
            throw new RuntimeException("Environment variable $name is not set");
        }

        return $value;
    }

    /**
     * Get the connection configuration for the test database
     *
     * @param string $driver
     *
     * @return Config
     */
    final protected static function getConnectionConfig(string $driver): Config
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
     * Create a database connection
     *
     * @param string $driver
     *
     * @return Connection
     *
     * @internal Only the trait itself should call this method
     */
    final protected static function connectToDatabase(string $driver): Connection
    {
        return new Connection(self::getConnectionConfig($driver));
    }

    /**
     * Set up the database connections
     *
     * @return void
     *
     * @internal Only the trait itself should call this method
     */
    final protected static function initializeDatabases(): void
    {
        foreach (self::SUPPORTED_ADAPTERS as $driver) {
            if (isset($_SERVER[strtoupper($driver) . '_TESTDB'])) {
                if (! isset(self::$connections[$driver])) {
                    self::$connections[$driver] = [self::connectToDatabase($driver)];
                    static::tearDownSchema(self::$connections[$driver][0], $driver);
                    static::setUpSchema(self::$connections[$driver][0], $driver);
                }
            }
        }
    }
}
