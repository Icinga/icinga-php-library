<?php

namespace ipl\Sql;

use ipl\Stdlib\Str;
use OutOfRangeException;

/**
 * SQL connection configuration
 */
class Config
{
    /** @var ?string Type of the DBMS */
    public ?string $db = null;

    /** @var ?string Database host */
    public ?string $host = null;

    /** @var string|int|null Database port */
    public string|int|null $port = null;

    /** @var ?string Database name */
    public ?string $dbname = null;

    /** @var ?string Username to use for authentication */
    public ?string $username = null;

    /** @var ?string Password to use for authentication */
    public ?string $password = null;

    /**
     * Character set for the connection
     *
     * If you want to use the default charset as configured by the database, don't set this property.
     *
     * @var string
     */
    public string $charset = '';

    /**
     * PDO connect options
     *
     * Array of key-value pairs that should be set when calling {@link Connection::connect()} in order to establish a DB
     * connection.
     *
     * @var array
     */
    public array $options = [];

    /** @var array Extra settings e.g. for SQL SSL connections */
    protected array $extraSettings = [];

    /**
     * Create a new SQL connection configuration from the given configuration key-value pairs
     *
     * Keys will be converted to camelCase, e.g. use_ssl → useSsl.
     *
     * @param iterable<string, mixed> $config Configuration key-value pairs
     */
    public function __construct(iterable $config)
    {
        foreach ($config as $key => $value) {
            $key = Str::camel($key);
            $this->$key = $value;
        }
    }

    public function __isset(string $name): bool
    {
        return isset($this->extraSettings[$name]);
    }

    public function __get(string $name): mixed
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
