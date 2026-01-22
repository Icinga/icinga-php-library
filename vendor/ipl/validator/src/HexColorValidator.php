<?php

namespace ipl\Validator;

use ipl\I18n\Translation;

/**
 * Validator for color input controls
 */
class HexColorValidator extends BaseValidator
{
    use Translation;

    /**
     * Check whether the given color is valid
     *
     * @param string $value
     *
     * @return bool
     */
    public function isValid($value): bool
    {
        // Multiple isValid() calls must not stack validation messages
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
