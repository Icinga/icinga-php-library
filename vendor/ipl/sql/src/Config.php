<?php

namespace ipl\Sql;

use InvalidArgumentException;

use function ipl\Stdlib\get_php_type;

/**
 * SQL connection configuration
 */
class Config
{
    /**
     * Create a new SQL connection configuration from the given configuration key-value pairs
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
            $this->$key = $value;
        }
    }

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
}
