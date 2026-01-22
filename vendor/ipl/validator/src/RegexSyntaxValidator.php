<?php

namespace ipl\Validator;

/**
 * Validate a PHP regular expression, syntax: /PATTERN/MODIFIERS
 */
class RegexSyntaxValidator extends BaseValidator
{
    /**
     * @param string $value
     *
     * @return bool
     */
    public function isValid($value): bool
    {
        $this->clearMessages();

        if (! is_string($value)) {
            $this->addMessage($this->translate('Invalid regex given, must be a string'));

            return false;
        }

        if (@preg_match($value, '') === false) {
            $this->addMessage(sprintf(
                $this->translate('Regex "%s" failed to compile: %s'),
                $value,
                preg_last_error_msg()
            ));

            return false;
        }

        return true;
    }
}
