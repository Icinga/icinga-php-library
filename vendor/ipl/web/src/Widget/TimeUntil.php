<?php

namespace ipl\Web\Widget;

use Icinga\Date\DateFormatter;
use ipl\Html\BaseHtmlElement;

class TimeUntil extends BaseHtmlElement
{
    /** @var int */
    protected $until;

    protected $tag = 'time';

    protected $defaultAttributes = ['class' => 'time-until'];

    public function __construct($until)
    {
        $this->until = (int) $until;
    }

    protected function assemble()
    {
        $dateTime = DateFormatter::formatDateTime($this->until);

        $this->addAttributes([
            'datetime' => $dateTime,
            'title'    => $dateTime
        ]);

        $this->add(DateFormatter::timeUntil($this->until));
    }
}
