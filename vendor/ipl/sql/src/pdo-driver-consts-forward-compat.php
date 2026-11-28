<?php

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

namespace Pdo;

use PDO;

use const PHP_VERSION_ID;

/*
 * Forward compatibility shim for PDO MySQL driver constants.
 *
 * PHP 8.4 introduced driver-specific constants on Pdo\Mysql while older
 * versions only expose PDO::MYSQL_* constants. This shim provides
 * Pdo\Mysql::ATTR_* for PHP < 8.4.
 */
if (PHP_VERSION_ID < 80400 && extension_loaded('pdo_mysql')) {
    /**
     * Constants available with mysqlnd and libmysqlclient.
     */
    trait MysqlCommonConstants
    {
        public const ATTR_USE_BUFFERED_QUERY = PDO::MYSQL_ATTR_USE_BUFFERED_QUERY;
        public const ATTR_LOCAL_INFILE = PDO::MYSQL_ATTR_LOCAL_INFILE;
        public const ATTR_SSL_KEY = PDO::MYSQL_ATTR_SSL_KEY;
        public const ATTR_SSL_CERT = PDO::MYSQL_ATTR_SSL_CERT;
        public const ATTR_SSL_CA = PDO::MYSQL_ATTR_SSL_CA;
        public const ATTR_SSL_CAPATH = PDO::MYSQL_ATTR_SSL_CAPATH;
        public const ATTR_SSL_CIPHER = PDO::MYSQL_ATTR_SSL_CIPHER;
        public const ATTR_INIT_COMMAND = PDO::MYSQL_ATTR_INIT_COMMAND;
        public const ATTR_COMPRESS = PDO::MYSQL_ATTR_COMPRESS;
        public const ATTR_DIRECT_QUERY = PDO::MYSQL_ATTR_DIRECT_QUERY;
        public const ATTR_FOUND_ROWS = PDO::MYSQL_ATTR_FOUND_ROWS;
        public const ATTR_IGNORE_SPACE = PDO::MYSQL_ATTR_IGNORE_SPACE;
        public const ATTR_SERVER_PUBLIC_KEY = PDO::MYSQL_ATTR_SERVER_PUBLIC_KEY;
        public const ATTR_MULTI_STATEMENTS = PDO::MYSQL_ATTR_MULTI_STATEMENTS;
        public const ATTR_LOCAL_INFILE_DIRECTORY = PDO::MYSQL_ATTR_LOCAL_INFILE_DIRECTORY;
    }

    if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
        // mysqlnd variant: includes ATTR_SSL_VERIFY_SERVER_CERT.
        // ATTR_READ_DEFAULT_* and ATTR_MAX_BUFFER_SIZE are not available with mysqlnd.
        class Mysql
        {
            use MysqlCommonConstants;

            public const ATTR_SSL_VERIFY_SERVER_CERT = PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT;
        }
    } else {
        // non-mysqlnd variant: exposes ATTR_READ_DEFAULT_* and ATTR_MAX_BUFFER_SIZE.
        // ATTR_SSL_VERIFY_SERVER_CERT is not available.
        class Mysql
        {
            use MysqlCommonConstants;

            public const ATTR_READ_DEFAULT_FILE = PDO::MYSQL_ATTR_READ_DEFAULT_FILE;
            public const ATTR_READ_DEFAULT_GROUP = PDO::MYSQL_ATTR_READ_DEFAULT_GROUP;
            public const ATTR_MAX_BUFFER_SIZE = PDO::MYSQL_ATTR_MAX_BUFFER_SIZE;
        }
    }
}
