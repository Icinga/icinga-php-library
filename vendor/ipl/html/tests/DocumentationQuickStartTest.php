<?php

namespace ipl\Tests\Html;

use ipl\Html\Html;

class DocumentationQuickStartTest extends TestCase
{
    public function testEscapingStrings()
    {
        $this->assertRendersHtml(
            '<h1>Hello there!</h1>',
            Html::tag('h1', 'Hello there!')
        );
    }

    public function testHtmlTagHelper()
    {
        $this->assertEquals(
            '<h1>Hello there!</h1>',
            Html::h1('Hello there!')
        );
        $this->assertEquals(
            '<h1>Hello &lt;&gt; world!</h1>',
            Html::tag('h1', 'Hello <> world!')
        );
    }

    public function testContentSeparator()
    {
        $this->assertEquals(
            '<h1>Hello&lt;name&gt;outthere!</h1>',
            Html::tag('h1', ['Hello', '<name>', 'out' ,'there!'])
        );
        $this->assertEquals(
            '<h1>Hello &lt;name&gt; out there!</h1>',
            Html::tag('h1', ['Hello', '<name>', 'out' ,'there!'])->setSeparator(' ')
        );
        $this->assertEquals(
            '<h1>Hello * out * there!</h1>',
            Html::tag('h1', ['Hello', 'out' ,'there!'])->setSeparator(' * ')
        );
    }

    public function testHtmlAttributes()
    {
        $this->assertEquals(
            '<p class="error">Something failed</p>',
            Html::tag('p', ['class' => 'error'], 'Something failed')
        );
        $this->assertEquals(
            '<ul role="menu"></ul>',
            Html::tag('ul', ['role' => 'menu'])
        );
    }

    public function testNestedElements()
    {
        $this->assertRendersHtml(
            '<ul role="menu"><li>A point</li></ul>',
            Html::tag('ul', ['role' => 'menu'], Html::tag('li', 'A point'))
        );
        $this->assertRendersHtml(
            '<ul role="menu"><li>First point</li><li>Second point</li><li>Third point</li></ul>',
            Html::tag('ul', ['role' => 'menu'], [
                Html::tag('li', 'First point'),
                Html::tag('li', 'Second point'),
                Html::tag('li', 'Third point'),
            ])
        );
        $this->assertRendersHtml(
            '<p>Hi <strong>there</strong>, are you ok?</p>',
            Html::tag('p', [
                'Hi ',
                Html::tag('strong', 'there'),
                ', are you ok?'
            ])
        );
    }

    public function testWrapEachForListItems()
    {
        $list = ['First point', 'Second point', 'Third point'];
        $this->assertRendersHtml(
            '<ul role="menu"><li>First point</li><li>Second point</li><li>Third point</li></ul>',
            Html::tag('ul', ['role' => 'menu'], Html::wrapEach($list, 'li'))
        );
    }

    public function testWrapEachWithCallback()
    {
        $options = [
            'jd' => 'John Doe',
            'pp' => 'Purple Princess',
            'su' => 'Some User',
        ];
        $select = Html::tag('select', Html::wrapEach($options, function ($name, $value) {
            return Html::tag('option', [
                'value' => $name
            ], $value);
        })->setSeparator("\n"))->setSeparator("\n");

        $this->assertEquals(
            '<select>' . "\n"
            . '<option value="jd">John Doe</option>' . "\n"
            . '<option value="pp">Purple Princess</option>' . "\n"
            . '<option value="su">Some User</option>' . "\n"
            . '</select>',
            $select->render()
        );
    }

    public function testFormattedStrings()
    {
        $this->assertEquals(
            'Hi <strong>there</strong>, are you ok?',
            Html::sprintf(
                'Hi %s, are you ok?',
                Html::strong('there')
            )
        );
    }
}
