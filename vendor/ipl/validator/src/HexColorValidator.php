<?php

namespace ipl\Validator;

/**
 * Validate a hex color string
 */
class HexColorValidator extends BaseValidator
{
    /**
     * Check whether the given color is valid
     *
     * @param string $value
     *
     * @return bool
     */
    public function isValid($value): bool
    {
        // Reset messages from a previous isValid() call.
        $this->clearMessages();

        if (! preg_match('/\A#[0-9a-f]{6}\z/i', $value)) {
            $this->addMessage(sprintf(
                $this->translate('Color string not in the expected format %s'),
                '#rrggbb'
            ));

            return false;
        }

        return true;
    }
}
