<?php

namespace ipl\Html\FormElement;

use DateTime;

class LocalDateTimeElement extends InputElement
{
    const FORMAT = 'Y-m-d\TH:i:s';

    protected $type = 'datetime-local';

    /** @var DateTime */
    protected $value;

    public function setValue($value)
    {
        if (is_string($value)) {
            $value = DateTime::createFromFormat(static::FORMAT, $value);
        }

        return parent::setValue($value);
    }

    public function getValueAttribute()
    {
        return $this->value->format(static::FORMAT);
    }
}
