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

        if (! empty($config->useSsl)) {
            if (! empty($config->sslKey)) {
                $options[PDO::MYSQL_ATTR_SSL_KEY] = $config->sslKey;
            }

            if (! empty($config->sslCert)) {
                $options[PDO::MYSQL_ATTR_SSL_CERT] = $config->sslCert;
            }

            if (! empty($config->sslCa)) {
                $options[PDO::MYSQL_ATTR_SSL_CA] = $config->sslCa;
            }

            if (! empty($config->sslCapath)) {
                $options[PDO::MYSQL_ATTR_SSL_CAPATH] = $config->sslCapath;
            }

            if (! empty($config->sslCipher)) {
                $options[PDO::MYSQL_ATTR_SSL_CIPHER] = $config->sslCipher;
            }

            if (
                defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')
                && ! empty($config->sslDoNotVerifyServerCert)
            ) {
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
            }
        }

        return $options;
    }
}
