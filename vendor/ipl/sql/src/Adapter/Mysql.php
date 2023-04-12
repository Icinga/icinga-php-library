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

        if (! empty($config->use_ssl)) {
            if (! empty($config->ssl_key)) {
                $options[PDO::MYSQL_ATTR_SSL_KEY] = $config->ssl_key;
            }

            if (! empty($config->ssl_cert)) {
                $options[PDO::MYSQL_ATTR_SSL_CERT] = $config->ssl_cert;
            }

            if (! empty($config->ssl_ca)) {
                $options[PDO::MYSQL_ATTR_SSL_CA] = $config->ssl_ca;
            }

            if (! empty($config->ssl_capath)) {
                $options[PDO::MYSQL_ATTR_SSL_CAPATH] = $config->ssl_capath;
            }

            if (! empty($config->ssl_cipher)) {
                $options[PDO::MYSQL_ATTR_SSL_CIPHER] = $config->ssl_cipher;
            }

            if (
                defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')
                && ! empty($config->ssl_do_not_verify_server_cert)
            ) {
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
            }
        }

        return $options;
    }
}
