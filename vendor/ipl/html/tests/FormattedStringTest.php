<?php

namespace ipl\Tests\Html;

use ipl\Html\FormattedString;
use ipl\Html\Html;
use ipl\Tests\Html\TestDummy\ObjectThatCanBeCastedToString;

class FormattedStringTest extends TestCase
{
    public function testSupportsValidHtmlForStringArguments()
    {
        $this->assertHtml(
            'some <strong>text</strong>',
            FormattedString::create(
                'some %s',
                Html::tag('strong', 'text')
            )
        );
    }

    public function testEscapingForStringArguments()
    {
        $this->assertHtml(
            'some &lt;strong&gt;text&lt;/strong&gt;',
            FormattedString::create(
                'some %s',
                '<strong>text</strong>'
            )
        );
    }

    public function testEscapingForStringArgumentsThatCanBeTreatedLikeAString()
    {
        $this->assertHtml(
            'Some String &lt;:-)',
            FormattedString::create(
                '%s',
                new ObjectThatCanBeCastedToString()
            )
        );
    }

    public function testSprintfLikeBehaviorForNonStringArguments()
    {
        $this->assertHtml(
            'number 1',
            FormattedString::create(
                'number %d',
                1
            )
        );
    }

    public function testSupportsArrayArguments()
    {
        $this->assertHtml(
            '<span>some</span><strong>text</strong>',
            FormattedString::create(
                '%s',
                [
                    Html::tag('span', 'some'),
                    Html::tag('strong', 'text')
                ]
            )
        );
    }

    public function testBehavesLikeSprintfForNumericStrings()
    {
        $this->assertHtml(
            'ff 1 2.33',
            FormattedString::create(
                '%3$x %d %.2f',
                '1',
                '2.3333334',
                '255'
            )
        );
    }
}
