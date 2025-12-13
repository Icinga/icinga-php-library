<?php

namespace ipl\Validator;

use ipl\I18n\Translation;

/**
 * Validates an X.509 certificate
 */
class X509CertValidator extends BaseValidator
{
    use Translation;

    /**
     * @param String $value
     * @return bool
     */
    public function isValid($value): bool
    {
        // Multiple isValid() calls must not stack validation messages
        $this->clearMessages();

        if (preg_match('/\A\s*\w+:/', $value)) {
            $this->addMessage($this->translate('URLs are not allowed'));

            return false;
        }

        if (openssl_x509_parse($value) === false) {
            $this->addMessage($this->translate('Not a valid PEM-encoded X.509 certificate'));

            return false;
        }

        return true;
    }
}
