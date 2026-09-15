<?php

namespace ipl\Web\Compat;

use Icinga\Application\Icinga;
use Icinga\Application\Version;
use Icinga\Util\Csp;
use ipl\Web\Style;

/**
 * Use this class to define inline style which is compatible
 * with Icinga Web &lt; 2.12 and with CSP support in &gt;= 2.12
 */
class StyleWithNonce extends Style
{
    public function getNonce(): ?string
    {
        if ($this->nonce === null) {
            $this->nonce = '';
            if (version_compare(Version::VERSION, '2.12.0', '>=') && Icinga::app()->isWeb()) {
                $this->nonce = Csp::getStyleNonce() ?? '';
            }
        }

        return parent::getNonce();
    }
}
