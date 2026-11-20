<?php

namespace ipl\Sql;

use BadMethodCallException;
use Exception;
use Generator;
use InvalidArgumentException;
use ipl\Sql\Contract\Adapter;
use ipl\Sql\Contract\Quoter;
use ipl\Stdlib\Plugins;
use LogicException;
use PDO;
use PDOStatement;
use Throwable;

/**
 * Connection to a SQL database using the native PDO for database access
 */
class Connection implements Quoter
{
    use Plugins;

    /** @var Config */
    protected Config $config;

    /** @var ?PDO */
    protected ?PDO $pdo = null;

    /** @var ?QueryBuilder */
    protected ?QueryBuilder $queryBuilder = null;

    /** @var Adapter */
    protected Adapter $adapter;

    /**
     * Create a new database connection using the given config for initialising the options for the connection
     *
     * {@link init()} is called after construction.
     *
     * @param Config|iterable<string, mixed> $config
     *
     * @throws InvalidArgumentException If there's no adapter for the given database available
     */
    public function __construct(Config|iterable $config)
    {
        $config = $config instanceof Config ? $config : new Config($config);

        $this->addPluginLoader('adapter', __NAMESPACE__ . '\\Adapter');

        $adapter = $this->loadPlugin('adapter', $config->db);

        if (! $adapter) {
            throw new InvalidArgumentException("Can't load database adapter for '{$config->db}'.");
        }

        $this->adapter = new $adapter();
        $this->config = $config;

        $this->init();
    }

    /**
     * Proxy PDO method calls
     *
     * @param string $name The name of the PDO method to call
     * @param array $arguments Arguments for the method to call
     *
     * @return mixed
     *
     * @throws BadMethodCallException If the called method does not exist
     *
     */
    public function __call(string $name, array $arguments): mixed
    {
        $this->connect();

        if (! method_exists($this->pdo, $name)) {
            $class = get_class($this);
            $message = "Call to undefined method $class::$name";

            throw new BadMethodCallException($message);
        }

        return call_user_func_array([$this->pdo, $name], $arguments);
    }

    /**
     * Initialise the database connection
     *
     * If you have to adjust the connection after construction, override this method.
     */
    public function init(): void
    {
    }

    /**
     * Get the database adapter
     *
     * @return Adapter
     */
    public function getAdapter(): Adapter
    {
        return $this->adapter;
    }

    /**
     * Get the connection configuration
     *
     * @return Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * Get the query builder for the database connection
     *
     * @return QueryBuilder
     */
    public function getQueryBuilder(): QueryBuilder
    {
        if ($this->queryBuilder === null) {
            $this->queryBuilder = new QueryBuilder($this->adapter);
        }

        return $this->queryBuilder;
    }

    /**
     * Create and return the PDO instance
     *
     * This method is called via {@link connect()} to establish a database connection.
     * If the default PDO needs to be adjusted for a certain DBMS, override this method.
     *
     * @return PDO
     */
    protected function createPdoAdapter(): PDO
    {
        $adapter = $this->getAdapter();

        $config = $this->getConfig();

        return new PDO(
            $adapter->getDsn($config),
            $config->username,
            $config->password,
            $adapter->getOptions($config)
        );
    }

    /**
     * Connect to the database, if not already connected
     *
     * @return $this
     */
    public function connect(): static
    {
        if ($this->pdo !== null) {
            return $this;
        }

        $this->pdo = $this->createPdoAdapter();

        if (! empty($this->config->charset)) {
            $this->exec(sprintf('SET NAMES %s', $this->pdo->quote($this->config->charset)));
        }

        $this->adapter->setClientTimezone($this);

        return $this;
    }

    /**
     * Disconnect from the database
     *
     * @return $this
     */
    public function disconnect(): static
    {
        $this->pdo = null;

        return $this;
    }

    /**
     * Check whether the connection to the database is still available
     *
     * @param bool $reconnect Whether to automatically reconnect
     *
     * @return bool
     */
    public function ping(bool $reconnect = true): bool
    {
        try {
            $this->query('SELECT 1')->closeCursor();
        } catch (Throwable) {
            if (! $reconnect) {
                return false;
            }

            $this->disconnect();

            return $this->ping(false);
        }

        return true;
    }

    /**
     * Fetch and return all result rows as sequential array
     *
     * @param Select|string $stmt The SQL statement to prepare and execute.
     * @param ?array $values Values to bind to the statement
     *
     * @return array
     */
    public function fetchAll(Select|string $stmt, ?array $values = null): array
    {
        return $this->prepexec($stmt, $values)
            ->fetchAll();
    }

    /**
     * Fetch and return the first column of all result rows as sequential array
     *
     * @param Select|string $stmt The SQL statement to prepare and execute.
     * @param ?array $values Values to bind to the statement
     *
     * @return array
     */
    public function fetchCol(Select|string $stmt, ?array $values = null): array
    {
        return $this->prepexec($stmt, $values)
            ->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    /**
     * Fetch and return the first row of the result rows
     *
     * @param Select|string $stmt The SQL statement to prepare and execute.
     * @param ?array $values Values to bind to the statement
     *
     * @return mixed
     */
    public function fetchOne(Select|string $stmt, ?array $values = null): mixed
    {
        return $this->prepexec($stmt, $values)
            ->fetch();
    }

    /**
     * Alias of {@link fetchOne()}
     */
    public function fetchRow(Select|string $stmt, ?array $values = null): mixed
    {
        return $this->prepexec($stmt, $values)
            ->fetch();
    }

    /**
     * Fetch and return all result rows as an array of key-value pairs
     *
     * First column is the key and the second column is the value.
     *
     * @param Select|string $stmt The SQL statement to prepare and execute.
     * @param ?array $values Values to bind to the statement
     *
     * @return array
     */
    public function fetchPairs(Select|string $stmt, ?array $values = null): array
    {
        return $this->prepexec($stmt, $values)
            ->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * Fetch and return the first column of the first result row
     *
     * @param Select|string $stmt The SQL statement to prepare and execute.
     * @param ?array $values Values to bind to the statement
     *
     * @return mixed
     */
    public function fetchScalar(Select|string $stmt, ?array $values = null): mixed
    {
        return $this->prepexec($stmt, $values)
            ->fetchColumn(0);
    }

    /**
     * Yield each result row
     *
     * `Connection::yieldAll(Select|string $stmt [[, array $values], int $fetchMode [, mixed ...$fetchModeOptions]])`
     *
     * @param Select|string $stmt The SQL statement to prepare and execute.
     * @param mixed ...$args Values to bind to the statement, fetch mode for the statement, fetch mode options
     *
     * @return Generator
     */
    public function yieldAll(Select|string $stmt, ...$args): Generator
    {
        $values = null;

        if (! empty($args)) {
            if (is_array($args[0])) {
                $values = array_shift($args);
            }
        }

        $fetchMode = null;

        if (! empty($args)) {
            $fetchMode = array_shift($args);

            switch ($fetchMode) {
                case PDO::FETCH_KEY_PAIR:
                    foreach ($this->yieldPairs($stmt, $values) as $key => $value) {
                        yield $key => $value;
                    }

                    return;
                case PDO::FETCH_COLUMN:
                    if (empty($args)) {
                        $args[] = 0;
                    }

                    break;
            }
        }

        $sth = $this->prepexec($stmt, $values);

        if ($fetchMode !== null) {
            $sth->setFetchMode($fetchMode, ...$args);
        }

        foreach ($sth as $key => $row) {
            yield $key => $row;
        }
    }

    /**
     * Yield the first column of each result row
     *
     * @param Select|string $stmt The SQL statement to prepare and execute
     * @param ?array $values Values to bind to the statement
     *
     * @return Generator
     */
    public function yieldCol(Select|string $stmt, ?array $values = null): Generator
    {
        $sth = $this->prepexec($stmt, $values);

        $sth->setFetchMode(PDO::FETCH_COLUMN, 0);

        foreach ($sth as $key => $row) {
            yield $key => $row;
        }
    }

    /**
     * Yield key-value pairs with the first column as key and the second column as value for each result row
     *
     * @param Select|string $stmt The SQL statement to prepare and execute
     * @param ?array $values Values to bind to the statement
     *
     * @return Generator
     */
    public function yieldPairs(Select|string $stmt, ?array $values = null): Generator
    {
        $sth = $this->prepexec($stmt, $values);

        $sth->setFetchMode(PDO::FETCH_NUM);

        foreach ($sth as $row) {
            [$key, $value] = $row;

            yield $key => $value;
        }
    }

    /**
     * Prepare and execute the given statement
     *
     * @param Delete|Insert|Select|Update|string $stmt   The SQL statement to prepare and execute
     * @param string|array                       $values Values to bind to the statement, if any
     *
     * @return PDOStatement
     */
    public function prepexec($stmt, $values = null)
    {
        if ($values !== null && ! is_array($values)) {
            $values = [$values];
        }

        if (is_object($stmt)) {
            [$stmt, $values] = $this->getQueryBuilder()->assemble($stmt);
        }

        $this->connect();

        $sth = $this->pdo->prepare($stmt);
        $sth->execute($values);

        return $sth;
    }

    /**
     * Prepare and execute the given Select query
     *
     * @param Select $select
     *
     * @return PDOStatement
     */
    public function select(Select $select): PDOStatement
    {
        [$stmt, $values] = $this->getQueryBuilder()->assembleSelect($select);

        return $this->prepexec($stmt, $values);
    }

    /**
     * Insert a table row with the specified data
     *
     * @param string $table The table to insert data into. The table specification must be in one of the following
     *   formats: 'table' or 'schema.table'
     * @param iterable $data Row data in terms of column-value pairs
     *
     * @return PDOStatement
     */
    public function insert(string $table, iterable $data): PDOStatement
    {
        $insert = (new Insert())
            ->into($table)
            ->values($data);

        return $this->prepexec($insert);
    }

    /**
     * Get the ID of the last inserted row
     *
     * @param ?string $name The name of the sequence object from which the ID should be returned.
     *
     * @throws LogicException If no connection to the database is established
     * @return false|string
     */
    public function lastInsertId(?string $name = null): false|string
    {
        if ($this->pdo === null) {
            throw new LogicException(
                'Cannot get last insert ID because no connection to the database is established.'
            );
        }

        return $this->pdo->lastInsertId($name);
    }

    /**
     * Update table rows with the specified data, optionally based on a given condition
     *
     * @param string|array $table The table to update. The table specification must be in one of the following formats:
     *   'table', 'table alias', ['alias' => 'table']
     * @param iterable $data The columns to update in terms of column-value pairs
     * @param string|array|null $condition The WHERE condition
     * @param string $operator The operator to combine multiple conditions with, if the condition is in the array format
     *
     * @return PDOStatement
     */
    public function update(
        string|array $table,
        iterable $data,
        string|array|null $condition = null,
        string $operator = Sql::ALL
    ): PDOStatement {
        $update = (new Update())
            ->table($table)
            ->set($data);

        if ($condition !== null) {
            $update->where($condition, $operator);
        }

        return $this->prepexec($update);
    }

    /**
     * Delete table rows, optionally based on a given condition
     *
     * @param string|array $table The table to delete data from. The table specification must be in one of the following
     *   formats: 'table', 'table alias', ['alias' => 'table']
     * @param string|array|null $condition The WHERE condition
     * @param string $operator The operator to combine multiple conditions with, if the condition is in the array format
     *
     * @return PDOStatement
     */
    public function delete(
        string|array $table,
        string|array|null $condition = null,
        string $operator = Sql::ALL
    ): PDOStatement {
        $delete = (new Delete())
            ->from($table);

        if ($condition !== null) {
            $delete->where($condition, $operator);
        }

        return $this->prepexec($delete);
    }

    /**
     * Begin a transaction
     *
     * @return bool Whether the transaction was started successfully
     */
    public function beginTransaction(): bool
    {
        $this->connect();

        return $this->pdo->beginTransaction();
    }

    /**
     * Commit a transaction
     *
     * @return bool Whether the transaction was committed successfully
     */
    public function commitTransaction(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Roll back a transaction
     *
     * @return bool Whether the transaction was rolled back successfully
     */
    public function rollBackTransaction(): bool
    {
        return $this->pdo->rollBack();
    }

    /**
     * Run the given callback in a transaction
     *
     * @param callable $callback The callback to run in a transaction.
     *   This connection instance is passed as parameter to the callback
     *
     * @return mixed The return value of the callback
     *
     * @throws Exception If an error occurs when running the callback
     */
    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();

        try {
            $result = call_user_func($callback, $this);
            $this->commitTransaction();
        } catch (Exception $e) {
            $this->rollBackTransaction();

            throw $e;
        }

        return $result;
    }

    public function quoteIdentifier(string|array $identifiers): string
    {
        return $this->getAdapter()->quoteIdentifier($identifiers);
    }
}
