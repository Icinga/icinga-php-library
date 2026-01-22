<?php

namespace ipl\Sql;

use InvalidArgumentException;
use ipl\Stdlib\Str;
use OutOfRangeException;

use function ipl\Stdlib\get_php_type;

/**
 * SQL connection configuration
 */
class Config
{
    /** @var string Type of the DBMS */
    public $db;

    /** @var string Database host */
    public $host;

    /** @var int Database port */
    public $port;

    /** @var string Database name */
    public $dbname;

    /** @var string Username to use for authentication */
    public $username;

    /** @var string Password to use for authentication */
    public $password;

    /**
     * Character set for the connection
     *
     * If you want to use the default charset as configured by the database, don't set this property.
     *
     * @var string
     */
    public $charset;

    /**
     * PDO connect options
     *
     * Array of key-value pairs that should be set when calling {@link Connection::connect()} in order to establish a DB
     * connection.
     *
     * @var array
     */
    public $options;

    /** @var array Extra settings e.g. for SQL SSL connections */
    protected $extraSettings = [];

    /**
     * Create a new SQL connection configuration from the given configuration key-value pairs
     *
     * Keys will be converted to camelCase, e.g. use_ssl → useSsl.
     *
     * @param iterable $config Configuration key-value pairs
     *
     * @throws InvalidArgumentException If $config is not iterable
     */
    public function __construct($config)
    {
        if (! is_iterable($config)) {
            throw new InvalidArgumentException(sprintf(
                '%s expects parameter one to be iterable, got %s instead',
                __METHOD__,
                get_php_type($config)
            ));
        }

        foreach ($config as $key => $value) {
            $key = Str::camel($key);
            $this->$key = $value;
        }
    }

    public function __isset(string $name): bool
    {
        return isset($this->extraSettings[$name]);
    }

    public function __get(string $name)
    {
        if (array_key_exists($name, $this->extraSettings)) {
            return $this->extraSettings[$name];
        }

        throw new OutOfRangeException(sprintf('Property %s does not exist', $name));
    }

    public function __set(string $name, $value): void
    {
        $this->extraSettings[$name] = $value;
    }
}
