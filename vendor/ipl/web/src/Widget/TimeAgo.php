<?php

namespace ipl\Web\Widget;

use DateTime;
use Exception;
use ipl\Html\Attributes;
use ipl\I18n\Translation;

class TimeAgo extends Time
{
    protected $defaultAttributes = ['class' => 'time-ago', 'data-relative-time' => 'ago'];

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
        [$time, $type, $interval] = $this->diff($this->compareTime);

        return sprintf(
            match ($type) {
                self::RELATIVE => $this->translate('%s ago', 'An event that happened the given time interval ago'),
                self::TIME     => $this->translate('at %s', 'An event happened at the given time'),
                self::DATE     => $this->translate('on %s', 'An event happened on the given date or date and time'),
            },
            $time
        );
    }
}
