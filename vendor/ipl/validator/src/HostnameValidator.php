<?php

namespace ipl\Validator;

/**
 * Validate a hostname
 */
class HostnameValidator extends BaseValidator
{
    /**
     * Check whether the value is a valid hostname per RFC 1034, RFC 1035, RFC 952, RFC 1123, RFC 2732, RFC 2181
     *
     * @param string $value
     *
     * @return bool
     */
    public function isValid($value): bool
    {
        $this->clearMessages();

        $asciiHostname = idn_to_ascii($value, 0, INTL_IDNA_VARIANT_UTS46);
        if (filter_var($asciiHostname, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
            $this->addMessage(sprintf(
                $this->translate("%s is not a valid host name."),
                $value
            ));

            return false;
        }

        return true;
    }
}
