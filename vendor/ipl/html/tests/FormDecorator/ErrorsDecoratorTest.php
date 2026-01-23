<?php

namespace ipl\Tests\Html\FormDecorator;

use ipl\Html\Contract\FormElement;
use ipl\Html\FormDecoration\FormElementDecorationResult;
use ipl\Html\FormDecoration\ErrorsDecorator;
use ipl\Html\FormElement\TextElement;
use ipl\Tests\Html\TestCase;

class ErrorsDecoratorTest extends TestCase
{
    protected ErrorsDecorator $decorator;

    public function setUp(): void
    {
        $this->decorator = new ErrorsDecorator();
    }

    public function testDefaultCssClassExists(): void
    {
        $this->assertNotEmpty($this->decorator->getClass());
    }

    public function testSetClass(): void
    {
        $decorator = $this->decorator->setClass('testing the setter');
        $this->assertSame('testing the setter', $decorator->getClass());

        $decorator->setClass(['testing the setter']);
        $this->assertSame(['testing the setter'], $decorator->getClass());

        $decorator->setClass(['testing', 'the', 'setter']);
        $this->assertSame(['testing', 'the', 'setter'], $decorator->getClass());

        $decorator->setClass([]);
        $this->assertSame([], $decorator->getClass());

        $decorator->setClass('');
        $this->assertSame('', $decorator->getClass());
    }

    public function testWithoutErrorMessages(): void
    {
        $results = new FormElementDecorationResult();
        $this->decorator->decorateFormElement($results, new TextElement('test'));

        $this->assertSame('', $results->assemble()->render());
    }

    public function testWithErrorMessages(): void
    {
        $results = new FormElementDecorationResult();
        $this->decorator->decorateFormElement(
            $results,
            (new TextElement('test'))->setMessages(['First error', 'Second error'])
        );

        $html = <<<HTML
<ul class="form-element-errors">
  <li>First error</li>
  <li>Second error</li>
</ul>
HTML;

        $this->assertHtml($html, $results->assemble());
    }

    public function testNonHtmlFormElementsAreSupported(): void
    {
        $results = new FormElementDecorationResult();
        $element = $this->createStub(FormElement::class);
        $element->method('getMessages')->willReturn(['First error', 'Second error']);

        $this->decorator->decorateFormElement($results, $element);

        $html = <<<HTML
<ul class="form-element-errors">
  <li>First error</li>
  <li>Second error</li>
</ul>
HTML;

        $this->assertHtml($html, $results->assemble());
    }
}
