<?php

namespace ipl\Tests\Html\TestDummy;

use ipl\Html\HtmlDocument;

class AddsContentDuringAssemble extends HtmlDocument
{
    protected function assemble()
    {
        $this->add('content');
    }
}
