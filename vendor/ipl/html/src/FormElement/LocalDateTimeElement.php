<?php

namespace ipl\Html\FormElement;

use DateTime;
use ipl\Validator\DateTimeValidator;
use ipl\Validator\ValidatorChain;

class LocalDateTimeElement extends InputElement
{
    public const FORMAT = 'Y-m-d\TH:i:s';

    protected $type = 'datetime-local';

    protected $defaultAttributes = ['step' => '1'];

    /** @var DateTime */
    protected $value;

    public function setValue($value)
    {
        if (is_string($value)) {
            $originalVal = $value;
            $value = DateTime::createFromFormat(static::FORMAT, $value);
            // In Chrome, if the seconds are set to 00, DateTime::createFromFormat() returns false.
            // Create DateTime without seconds in format
            if ($value === false) {
                $format = substr(static::FORMAT, 0, strrpos(static::FORMAT, ':') ?: null);
                $value = DateTime::createFromFormat($format, $originalVal);
            }

            if ($value === false) {
                $value = $originalVal;
            }
        }

        return parent::setValue($value);
    }

    public function getValueAttribute()
    {
        if (! $this->value instanceof DateTime) {
            return $this->value;
        }

        return $this->value->format(static::FORMAT);
    }

    protected function addDefaultValidators(ValidatorChain $chain): void
    {
        $chain->add(new DateTimeValidator());
    }
}
