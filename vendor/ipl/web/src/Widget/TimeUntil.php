<?php

namespace ipl\Web\Widget;

use DateTime;
use Exception;
use ipl\Html\Attributes;
use ipl\I18n\Translation;

class TimeUntil extends Time
{
    protected $defaultAttributes = ['class' => 'time-until', 'data-relative-time' => 'until'];

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

        if ($interval->days === 0 && $interval->h === 0) {
            $this->addAttributes(
                Attributes::create(
                    [
                        'data-ago-label' => sprintf(
                            $this->translate('%s ago', 'An event that happened the given time interval ago'),
                            '0m 0s'
                        )
                    ]
                )
            );
        }

        if (
            $interval->invert !== 1 && $type === static::RELATIVE
            && ($interval->days !== 0 || $interval->h !== 0 || $interval->i !== 0 || $interval->s !== 0)
        ) {
            $time = '-' . $time;
        }

        return sprintf(
            match ($type) {
                self::RELATIVE => $this->translate(
                    'in %s',
                    'An event will happen after the given time interval has elapsed'
                ),
                self::TIME     => $this->translate('at %s', 'An event will happen at the given time'),
                self::DATE     => $this->translate('on %s', 'An event will happen on the given date or date and time'),
            },
            $time
        );
    }
}
