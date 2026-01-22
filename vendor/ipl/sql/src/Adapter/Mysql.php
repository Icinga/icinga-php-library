<?php

namespace ipl\Sql\Adapter;

use ipl\Sql\Config;
use ipl\Sql\Connection;
use PDO;

class Mysql extends BaseAdapter
{
    protected $quoteCharacter = ['`', '`'];

    protected $escapeCharacter = '``';

    public function setClientTimezone(Connection $db)
    {
        $db->exec('SET time_zone = ' . $db->quote($this->getTimezoneOffset()));

        return $this;
    }

    public function getOptions(Config $config)
    {
        $options = parent::getOptions($config);
        // In PHP 8.5+, driver-specific constants of the PDO class are deprecated,
        // but the replacements are only available since php 8.4
        if (version_compare(PHP_VERSION, '8.4.0', '<')) {
            $mysqlConstantPrefix = 'PDO::MYSQL_ATTR_';
        } else {
            $mysqlConstantPrefix = 'Pdo\Mysql::ATTR_';
        }

        if (! empty($config->useSsl)) {
            if (! empty($config->sslKey)) {
                $options[constant($mysqlConstantPrefix . 'SSL_KEY')] = $config->sslKey;
            }

            if (! empty($config->sslCert)) {
                $options[constant($mysqlConstantPrefix . 'SSL_CERT')] = $config->sslCert;
            }

            if (! empty($config->sslCa)) {
                $options[constant($mysqlConstantPrefix . 'SSL_CA')] = $config->sslCa;
            }

            if (! empty($config->sslCapath)) {
                $options[constant($mysqlConstantPrefix . 'SSL_CAPATH')] = $config->sslCapath;
            }

            if (! empty($config->sslCipher)) {
                $options[constant($mysqlConstantPrefix . 'SSL_CIPHER')] = $config->sslCipher;
            }

            if (
                defined($mysqlConstantPrefix . 'SSL_VERIFY_SERVER_CERT')
                && ! empty($config->sslDoNotVerifyServerCert)
            ) {
                $options[constant($mysqlConstantPrefix . 'SSL_VERIFY_SERVER_CERT')] = false;
            }
        }

        return $options;
    }
}
