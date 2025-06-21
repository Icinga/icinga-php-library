<?php

namespace ipl\Web\Widget;

use Icinga\Date\DateFormatter;
use ipl\Html\BaseHtmlElement;

class TimeSince extends BaseHtmlElement
{
    /** @var int */
    protected $since;

    protected $tag = 'time';

    protected $defaultAttributes = ['class' => 'time-since'];

    public function __construct($since)
    {
        $this->since = (int) $since;
    }

    protected function assemble()
    {
        $dateTime = DateFormatter::formatDateTime($this->since);

        $this->addAttributes([
            'datetime' => $dateTime,
            'title'    => $dateTime
        ]);

        $this->add(DateFormatter::timeSince($this->since));
    }
}
