<?php

namespace ipl\Validator;

use DateTime;
use ipl\I18n\Translation;

/**
 * Validator for date-and-time input controls
 */
class DateTimeValidator extends BaseValidator
{
    use Translation;

    /** @var string Default date time format */
    const FORMAT = 'Y-m-d\TH:i:s';

    /** @var bool Whether to use the default date time format */
    protected $local;

    /**
     * Create a new date-and-time input control validator
     *
     * @param bool $local
     */
    public function __construct($local = true)
    {
        $this->local = (bool) $local;
    }

    /**
     * Check whether the given date time is valid
     *
     * @param   string|DateTime $value
     *
     * @return  bool
     */
    public function isValid($value)
    {
        // Multiple isValid() calls must not stack validation messages
        $this->clearMessages();

        if (! $value instanceof DateTime && ! is_string($value)) {
            $this->addMessage($this->translate('Invalid date/time given.'));

            return false;
        }

        if (! $value instanceof DateTime) {
            $format = $this->local === true ? static::FORMAT : DateTime::RFC3339;
            $dateTime = DateTime::createFromFormat($format, $value);

            if ($dateTime === false || $dateTime->format($format) !== $value) {
                $this->addMessage(sprintf(
                    $this->translate("Date/time string not in the expected format: %s"),
                    $format
                ));

                return false;
            }
        }

        return true;
    }
}
