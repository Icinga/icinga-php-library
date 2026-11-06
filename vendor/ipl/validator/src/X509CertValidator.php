<?php

namespace ipl\Validator;

/**
 * Validate a PEM-encoded X.509 certificate
 */
class X509CertValidator extends BaseValidator
{
    /**
     * Check whether the value is a valid PEM-encoded X.509 certificate
     *
     * @param string $value
     *
     * @return bool
     */
    public function isValid($value): bool
    {
        // Reset messages from a previous isValid() call.
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
