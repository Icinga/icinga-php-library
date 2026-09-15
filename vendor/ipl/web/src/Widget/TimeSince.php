<?php

namespace ipl\Web\Widget;

use DateTime;
use Exception;
use ipl\I18n\Translation;

class TimeSince extends Time
{
    protected $defaultAttributes = ['class' => 'time-since', 'data-relative-time' => 'since'];

    /**
     * @param int|float|DateTime|null $time Time as timestamp, DateTime object, or null for current time
     * @param ?DateTime $compareTime Time to compare with, null for current time
     *
     * @throws Exception
     */
    public function __construct(int|float|DateTime|null $time = null, ?DateTime $compareTime = null)
    {
        $this->compareTime = $compareTime;

        if (! $time instanceof DateTime) {
            $time = $this->castToDateTime($time);
        }

        parent::__construct($time);
    }

    protected function format(): string
    {
        [$time, $type] = $this->diff($this->compareTime);

        return sprintf(
            match ($type) {
                self::RELATIVE         => $this->translate(
                    'for %s',
                    'A status is lasting for the given time interval'
                ),
                self::TIME, self::DATE => $this->translate(
                    'since %s',
                    'A status is lasting since the given time, date or date and time'
                ),
            },
            $time
        );
    }
}
