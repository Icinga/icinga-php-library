<?php

namespace ipl\Tests\Html;

use InvalidArgumentException;
use ipl\Html\Html;

class HtmlTest extends TestCase
{
    public function testTagSupportsIterable()
    {
        $content = function () {
            yield Html::tag('b', 'foo');
            yield Html::tag('b', 'bar');
        };

        $html = Html::tag('div', $content());

        $this->assertHtml('<div><b>foo</b><b>bar</b></div>', $html);

        $html = Html::tag('div', ['class' => 'foobar'], $content());

        $this->assertHtml('<div class="foobar"><b>foo</b><b>bar</b></div>', $html);
    }

    public function testWrapsListsWithSimpleHtmlTags()
    {
        $this->assertHtml(
            '<ul><li>a</li><li>b</li><li>c</li></ul>',
            Html::tag('ul', Html::wrapEach(['a', 'b', 'c'], 'li'))
        );
        $this->assertHtml(
            '<ul class="simple"><li>a</li><li>b</li><li>c</li></ul>',
            Html::tag('ul', ['class' => 'simple'], Html::wrapEach(['a', 'b', 'c'], 'li'))
        );
    }

    public function testTagComplainsAboutAttributesNotBeingAttributes()
    {
        $this->expectException(InvalidArgumentException::class);
        Html::tag('span', ['foo-class'], ['foo-content']);
    }

    public function testTagDoesNotIgnoreContent()
    {
        $this->expectException(InvalidArgumentException::class);
        Html::tag('span', Html::tag('a'), Html::tag('b'));
    }

    public function testWrapsListsWithCallback()
    {
        $options = [
            'val1' => 'Label 1',
            'val2' => 'Label 2',
            'val3' => 'Label 3',
        ];
        $select = Html::tag('select', Html::wrapEach($options, function ($name, $value) {
            return Html::tag('option', [
                'value' => $name
            ], $value);
        }));

        $this->assertHtml(
            '<select>'
            . '<option value="val1">Label 1</option>'
            . '<option value="val2">Label 2</option>'
            . '<option value="val3">Label 3</option>'
            . '</select>',
            $select
        );
    }
}
