<?php

namespace ipl\Validator;

use ipl\I18n\Translation;

/**
 * Validates Host name
 */
class HostnameValidator extends BaseValidator
{
    use Translation;

    /**
     * Validates host names against RFC 1034, RFC 1035, RFC 952, RFC 1123, RFC 2732, RFC 2181, and RFC 1123
     *
     * @param string $value
     *
     * @return boolean
     */
    public function isValid($value)
    {
        $this->clearMessages();

        $asciiHostname = idn_to_ascii($value, 0, INTL_IDNA_VARIANT_UTS46);
        if (filter_var($asciiHostname, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
            $this->addMessage(sprintf(
                $this->translate("%s is not a valid host name."),
                $value ?? ''
            ));

            return false;
        }

        return true;
    }
}
