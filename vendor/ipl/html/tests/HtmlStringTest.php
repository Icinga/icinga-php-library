<?php

namespace ipl\Tests\Html;

use ipl\Html\HtmlString;
use ipl\Html\Text;

class HtmlStringTest extends TestCase
{
    public function testRendersRawHtml()
    {
        $string = HtmlString::create('<div>some text</div>');
        $this->assertRendersHtml('<div>some text</div>', $string);
    }

    public function testTextEscapesHtml()
    {
        $text = Text::create('This is true: 2 > 1');
        $this->assertEquals('This is true: 2 &gt; 1', $text->render());
    }

    public function testCanEscapeHtml()
    {
        $string = HtmlString::create('<div>some text</div>');
        $string->setEscaped(false);
        $this->assertEquals('&lt;div&gt;some text&lt;/div&gt;', $string->render());
    }
}
