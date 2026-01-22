<?php

namespace ipl\Validator;

use ipl\I18n\Translation;

/**
 * Validates a private key
 */
class PrivateKeyValidator extends BaseValidator
{
    use Translation;

    public function isValid($value)
    {
        // Multiple isValid() calls must not stack validation messages
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
