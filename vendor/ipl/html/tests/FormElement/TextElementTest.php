<?php

namespace ipl\Tests\Html\FormElement;

use ipl\Html\Attributes;
use ipl\Html\FormElement\TextElement;
use ipl\Tests\Html\TestCase;

class TextElementTest extends TestCase
{
    public function testRenderPlaceholderAttribute(): void
    {
        $this->assertHtml(
            '<input name="test" type="text" placeholder="Enter text"/>',
            new TextElement('test', new Attributes(['placeholder' => 'Enter text']))
        );

        $this->assertHtml(
            '<input name="test" type="text" placeholder="Enter text"/>',
            (new TextElement('test'))->setAttribute('placeholder', 'Enter text')
        );
    }
}
