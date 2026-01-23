<?php

namespace ipl\Tests\Html\FormDecorator;

use ipl\Html\Contract\FormElement;
use ipl\Html\FormDecoration\FormElementDecorationResult;
use ipl\Html\FormDecoration\DescriptionDecorator;
use ipl\Html\FormElement\FieldsetElement;
use ipl\Html\FormElement\TextElement;
use ipl\Tests\Html\TestCase;

class DescriptionDecoratorTest extends TestCase
{
    protected DescriptionDecorator $decorator;

    public function setUp(): void
    {
        $this->decorator = new DescriptionDecorator();
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

    public function testDecoratorCreatesRandomIdIfNotSpecified(): void
    {
        $element = new TextElement('test', ['description' => 'Testing']);

        $this->assertFalse($element->getAttributes()->has('id'));

        $this->decorator->decorateFormElement(new FormElementDecorationResult(), $element);

        $this->assertTrue($element->getAttributes()->has('id'));
        $this->assertNotEmpty($element->getAttributes()->get('id')->getValue());
    }

    public function testAttributeAriaDescribedByAlwaysExists(): void
    {
        $element = new TextElement('test', ['description' => 'Testing', 'id' => 'test-id']);
        $this->decorator->decorateFormElement(new FormElementDecorationResult(), $element);

        $this->assertTrue($element->getAttributes()->has('aria-describedby'));
        $this->assertSame('desc_test-id', $element->getAttributes()->get('aria-describedby')->getValue());

        // with random fallback id
        $element = new TextElement('test', ['description' => 'Testing']);
        $this->decorator->decorateFormElement(new FormElementDecorationResult(), $element);

        $this->assertTrue($element->getAttributes()->has('aria-describedby'));
        $this->assertNotEmpty($element->getAttributes()->get('aria-describedby')->getValue());
    }

    public function testWithDescriptionAndIdAttribute(): void
    {
        $results = new FormElementDecorationResult();
        $this->decorator->decorateFormElement(
            $results,
            new TextElement('test', ['description' => 'Testing', 'id' => 'test-id'])
        );

        $this->assertHtml('<p class="form-element-description" id="desc_test-id">Testing</p>', $results->assemble());
    }

    public function testWithEmptyDescriptionAttribute(): void
    {
        $results = new FormElementDecorationResult();
        $this->decorator->decorateFormElement(
            $results,
            new TextElement('test', ['description' => '', 'id' => 'test-id']) // added id to avoid random id
        );

        $this->assertHtml('<p class="form-element-description" id="desc_test-id"></p>', $results->assemble());
    }

    public function testWithoutDescriptionAttribute(): void
    {
        $results = new FormElementDecorationResult();
        $this->decorator->decorateFormElement($results, new TextElement('test'));

        $this->assertSame('', $results->assemble()->render());
    }

    public function testFieldsetElementsAreIgnored(): void
    {
        $results = new FormElementDecorationResult();
        $this->decorator->decorateFormElement($results, new FieldsetElement('test'));

        $this->assertSame('', $results->assemble()->render());
    }

    public function testNonHtmlFormElementsAreSupported(): void
    {
        $results = new FormElementDecorationResult();
        $element = $this->createMock(FormElement::class);
        $element->method('getDescription')->willReturn('Testing');
        $element->expects($this->never())->method('getAttributes');

        $this->decorator->decorateFormElement($results, $element);

        $this->assertHtml('<p class="form-element-description">Testing</p>', $results->assemble());
    }
}
