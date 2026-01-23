<?php

namespace ipl\Tests\Html\TestDummy;

use ipl\Html\Html;
use ipl\Html\HtmlDocument;

class AddsWrapperDuringAssemble extends HtmlDocument
{
    protected function assemble()
    {
        $this->setWrapper(Html::tag('div'));
    }
}
