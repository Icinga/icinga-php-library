<?php

namespace ipl\Sql\Adapter;

use ipl\Sql\Config;
use ipl\Sql\Connection;
use Pdo\Mysql as PdoMysql;

class Mysql extends BaseAdapter
{
    protected array $quoteCharacter = ['`', '`'];

    protected string $escapeCharacter = '``';

    public function setClientTimezone(Connection $db): static
    {
        $db->exec('SET time_zone = ' . $db->quote($this->getTimezoneOffset()));

        return $this;
    }

    public function getOptions(Config $config): array
    {
        $options = parent::getOptions($config);

        if (! empty($config->useSsl)) {
            if (! empty($config->sslKey)) {
                $options[PdoMysql::ATTR_SSL_KEY] = $config->sslKey;
            }

            if (! empty($config->sslCert)) {
                $options[PdoMysql::ATTR_SSL_CERT] = $config->sslCert;
            }

            if (! empty($config->sslCa)) {
                $options[PdoMysql::ATTR_SSL_CA] = $config->sslCa;
            }

            if (! empty($config->sslCapath)) {
                $options[PdoMysql::ATTR_SSL_CAPATH] = $config->sslCapath;
            }

            if (! empty($config->sslCipher)) {
                $options[PdoMysql::ATTR_SSL_CIPHER] = $config->sslCipher;
            }

            if (
                defined(PdoMysql::class . '::ATTR_SSL_VERIFY_SERVER_CERT')
                && ! empty($config->sslDoNotVerifyServerCert)
            ) {
                $options[PdoMysql::ATTR_SSL_VERIFY_SERVER_CERT] = false;
            }
        }

        return $options;
    }
}
