<?php

namespace ipl\Sql\Test;

use ipl\Sql\Connection;
use RuntimeException;

/**
 * Data provider for database connections. Use this to provide real database connections for your tests.
 *
 * To use it, implement {@see Databases::createSchema()} and {@see Databases::dropSchema()}.
 * The environment also needs to provide the following variables: (Replace * with the name of a supported adapter)
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
 * If you need to implement your own setUp() and tearDown() methods, make sure to call
 * {@see Databases::setUpDatabases()} and {@see Databases::tearDownDatabases()} respectively.
 */
trait Databases
{
    /**
     * Create the schema for the test database
     *
     * This is called once for each dataset and test case. (i.e. Twice per test-case)
     *
     * @param Connection $db
     * @param string $driver
     *
     * @return void
     */
    abstract protected function createSchema(Connection $db, string $driver): void;

    /**
     * Drop the schema of the test database
     *
     *  This is called once for each dataset and test case. (i.e. Twice per test-case)
     *
     * @param Connection $db
     * @param string $driver
     *
     * @return void
     */
    abstract protected function dropSchema(Connection $db, string $driver): void;

    /**
     * Provide the database connections
     *
     * @return array<string, Connection[]>
     */
    public function databases(): array
    {
        $supportedAdapters = ['mssql', 'mysql', 'oracle', 'pgsql', 'sqlite'];

        $connections = [];
        foreach ($supportedAdapters as $driver) {
            if (isset($_SERVER[strtoupper($driver) . '_TESTDB'])) {
                $connections[$driver] = [$this->createConnection($driver)];
            }
        }

        return $connections;
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
    private function getEnvVariable(string $name): string
    {
        $value = getenv($name);
        if ($value === false) {
            throw new RuntimeException("Environment variable $name is not set");
        }

        return $value;
    }

    /**
     * Create a database connection
     *
     * @param string $driver
     *
     * @return Connection
     */
    private function createConnection(string $driver): Connection
    {
        return new Connection([
            'db' => $driver,
            'host' => $this->getEnvVariable(strtoupper($driver) . '_TESTDB_HOST'),
            'port' => $this->getEnvVariable(strtoupper($driver) . '_TESTDB_PORT'),
            'username' => $this->getEnvVariable(strtoupper($driver) . '_TESTDB_USER'),
            'password' => $this->getEnvVariable(strtoupper($driver) . '_TESTDB_PASSWORD'),
            'dbname' => $this->getEnvVariable(strtoupper($driver) . '_TESTDB'),
        ]);
    }

    public function setUp(): void
    {
        $this->setUpDatabases();
    }

    protected function setUpDatabases(): void
    {
        if (method_exists($this, 'dataName') && method_exists($this, 'getProvidedData')) {
            // A small performance improvement. Though, it relies on internal methods, hence the check.
            $providedData = $this->getProvidedData();
            if (! empty($providedData)) {
                $this->createSchema($providedData[0], $this->dataName());
            }
        } else {
            $this->createSchema($this->createConnection('mysql'), 'mysql');
            $this->createSchema($this->createConnection('pgsql'), 'pgsql');
        }
    }

    public function tearDown(): void
    {
        $this->tearDownDatabases();
    }

    protected function tearDownDatabases(): void
    {
        if (method_exists($this, 'dataName') && method_exists($this, 'getProvidedData')) {
            // A small performance improvement. Though, it relies on internal methods, hence the check.
            $providedData = $this->getProvidedData();
            if (! empty($providedData)) {
                $this->dropSchema($providedData[0], $this->dataName());
            }
        } else {
            $this->dropSchema($this->createConnection('mysql'), 'mysql');
            $this->dropSchema($this->createConnection('pgsql'), 'pgsql');
        }
    }
}
