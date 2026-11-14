<?php

namespace ipl\Validator;

/**
 * Validate a PEM-encoded private key
 */
class PrivateKeyValidator extends BaseValidator
{
    /**
     * Check whether the value is a valid PEM-encoded private key
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

        if (openssl_pkey_get_private($value) === false) {
            $this->addMessage($this->translate('Not a valid PEM-encoded private key'));

            return false;
        }

        return true;
    }
}
