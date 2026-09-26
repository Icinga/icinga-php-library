<?php

namespace ipl\Sql\Test;

use ipl\Sql\Connection;
use ipl\Sql\Select;
use ipl\Sql\Test\SharedDatabases\SchemaGroup;
use ipl\Sql\Test\SharedDatabases\State;
use ipl\Sql\Test\SharedDatabases\TransactionIsolation;
use ReflectionClass;
use RuntimeException;
use Throwable;

/**
 * Data provider for database connections. Use this to provide real database connections for your tests.
 *
 * In contrast to {@see Databases}, this trait will only initialize the schema once for each test case.
 * When PHPUnit runs a test case, the schema will first be dropped and then re-created. This can be
 * customized using the {@see SchemaGroup} attribute which allows to share the same schema across
 * multiple test cases. Each individual test can be isolated in its own transaction by using the
 * {@see TransactionIsolation} attribute.
 *
 * To use it, implement {@see self::setUpSchema()} and {@see self::tearDownSchema()}.
 * The environment also needs to provide the following variables: (Replace * with the name of a supported adapter)
 * {@see State::SUPPORTED_ADAPTERS}
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
 * If you need access to the database connection outside a test case, use {@see self::getConnection()}.
 * If you need to implement your own setUp() method, make sure to call {@see self::rollbackChanges()}.
 * If you need to implement your own setUpBeforeClass() method, make sure to call {@see self::processAnnotations()}.
 */
trait SharedDatabases
{
    /**
     * @var bool Whether transaction isolation is used
     * @internal Only the trait {@see SharedDatabases} must access this property
     */
    private static bool $transactionIsolation = false;

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
        return State::databases();
    }

    /**
     * Get the current database connection
     *
     * @return ?Connection
     * @throws RuntimeException if the connection cannot be retrieved
     */
    final protected function getConnection(): ?Connection
    {
        if (method_exists($this, 'getProvidedData')) {
            $connections = $this->getProvidedData();
        } elseif (method_exists($this, 'providedData')) {
            $connections = $this->providedData();
        } else {
            throw new RuntimeException('Cannot get connection: Unsupported PHPUnit version?');
        }

        if (empty($connections)) {
            return null;
        }

        $connection = $connections[0];
        if (! $connection instanceof Connection) {
            throw new RuntimeException('Cannot get connection: Are all test cases using the same provider?');
        }

        return $connection;
    }

    /**
     * Roll back all changes made by any previous test run
     *
     * @return void
     */
    final protected function rollbackChanges(): void
    {
        if (! self::$transactionIsolation) {
            return;
        }

        $connection = $this->getConnection();
        if ($connection === null) {
            return;
        }

        while ($connection->inTransaction()) {
            $connection->rollBackTransaction();
        }

        $connection->beginTransaction();
    }

    /**
     * Re-create the database schemas if required
     *
     * @param string $group
     *
     * @return void
     */
    final protected static function maintainSchema(string $group): void
    {
        foreach (self::sharedDatabases() as $driver => [$connection]) {
            // Ensure to exit any active transaction a previous test case may have initiated
            while ($connection->inTransaction()) {
                $connection->rollBackTransaction();
            }

            try {
                $currentGroup = $connection->select(
                    (new Select())
                        ->columns('__test_group.name')
                        ->from('__test_group')
                )->fetchColumn();
            } catch (Throwable) {
                $currentGroup = null;
            }

            if ($currentGroup !== $group) {
                $connection->exec(sprintf(
                    'DROP TABLE IF EXISTS %s',
                    $connection->quoteIdentifier('__test_group')
                ));
                static::tearDownSchema($connection, $driver);
                static::setUpSchema($connection, $driver);
                $connection->exec(sprintf(
                    'CREATE TABLE %1$s (name VARCHAR(255))',
                    $connection->quoteIdentifier('__test_group')
                ));
                $connection->insert('__test_group', ['name' => $group]);
            }
        }
    }

    /**
     * Process trait-specific class annotations
     *
     * @param string $class
     *
     * @return void
     */
    final protected static function processAnnotations(string $class): void
    {
        $refClass = new ReflectionClass($class);

        $group = $class;
        $attributes = $refClass->getAttributes(SchemaGroup::class);
        if (! empty($attributes)) {
            $group = $attributes[0]->newInstance()->name;
        }

        self::maintainSchema(State::uniqueGroup($group));

        $attributes = $refClass->getAttributes(TransactionIsolation::class);
        self::$transactionIsolation = ! empty($attributes);
    }

    public static function setUpBeforeClass(): void
    {
        self::processAnnotations(static::class);
    }

    public function setUp(): void
    {
        $this->rollbackChanges();
    }
}
