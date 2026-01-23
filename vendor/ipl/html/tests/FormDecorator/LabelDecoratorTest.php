<?php

namespace ipl\Tests\Html\FormDecorator;

use ipl\Html\Contract\FormElement;
use ipl\Html\FormDecoration\FormElementDecorationResult;
use ipl\Html\FormDecoration\LabelDecorator;
use ipl\Html\FormElement\FieldsetElement;
use ipl\Html\FormElement\SubmitButtonElement;
use ipl\Html\FormElement\SubmitElement;
use ipl\Html\FormElement\TextElement;
use ipl\Tests\Html\TestCase;

class LabelDecoratorTest extends TestCase
{
    protected LabelDecorator $decorator;

    public function setUp(): void
    {
        $this->decorator = new LabelDecorator();
    }

    public function testDefaultCssClassExists(): void
    {
        $this->assertNotEmpty($this->decorator->getClass());
    }

    public function testSetClass(): void
    {
        $this->assertSame('', $this->decorator->setClass('')->getClass());
        $this->assertSame([], $this->decorator->setClass([])->getClass());
        $this->assertSame('testing the setter', $this->decorator->setClass('testing the setter')->getClass());
        $this->assertSame(['testing the setter'], $this->decorator->setClass(['testing the setter'])->getClass());
        $this->assertSame(
            ['testing', 'the', 'setter'],
            $this->decorator->setClass(['testing', 'the', 'setter'])->getClass()
        );
    }

    public function testDecoratorCreatesRandomIdIfNotSpecified(): void
    {
        $element = new TextElement('test', ['label' => '']);

        $this->assertFalse($element->getAttributes()->has('id'));

        $this->decorator->decorateFormElement(new FormElementDecorationResult(), $element);

        $this->assertTrue($element->getAttributes()->has('id'));
        $this->assertNotEmpty($element->getAttributes()->get('id')->getValue());
    }

    public function testAttributeForAlwaysExists(): void
    {
        $results = new FormElementDecorationResult();
        $this->decorator->decorateFormElement(
            $results,
            new TextElement('test', ['label' => 'Label Here', 'id' => 'test-id'])
        );

        $this->assertStringContainsString('for="test-id"', $results->assemble()->render());

        // with random fallback id
        $this->decorator->decorateFormElement(
            $results,
            new TextElement('test', ['label' => 'Label Here'])
        );

        $this->assertMatchesRegularExpression('/for="test[^"]+"/', $results->assemble()->render());
    }

    public function testWithLabelAndIdAttribute(): void
    {
        $results = new FormElementDecorationResult();
        $this->decorator->decorateFormElement(
            $results,
            new TextElement('test', ['label' => 'Label Here', 'id' => 'test-id'])
        );

        $this->assertHtml(
            '<label for="test-id" class="form-element-label">Label Here</label>',
            $results->assemble()
        );
    }

    public function testWithEmptyLabelAttribute(): void
    {
        $results = new FormElementDecorationResult();
        $this->decorator->decorateFormElement($results, new TextElement('test', ['label' => '', 'id' => 'test-id']));

        $this->assertHtml(
            '<label class="form-element-label" for="test-id"></label>',
            $results->assemble()
        );
    }

    public function testWithoutLabelAttribute(): void
    {
        $results = new FormElementDecorationResult();
        $this->decorator->decorateFormElement($results, new TextElement('test'));

        $this->assertSame('', $results->assemble()->render());
    }

    public function testThatSubmitElementsAndFieldsetElementsAreIgnored(): void
    {
        $results = new FormElementDecorationResult();
        $this->decorator->decorateFormElement($results, new FieldsetElement('test'));
        $this->decorator->decorateFormElement($results, new SubmitButtonElement('test'));
        $this->decorator->decorateFormElement($results, new SubmitElement('test'));

        $this->assertSame('', $results->assemble()->render());
    }

    public function testNonHtmlFormElementsAreSupported(): void
    {
        $results = new FormElementDecorationResult();
        $element = $this->createMock(FormElement::class);
        $element->method('getLabel')->willReturn('Testing');
        $element->expects($this->never())->method('getAttributes');

        $this->decorator->decorateFormElement($results, $element);

        $this->assertHtml('<label class="form-element-label">Testing</label>', $results->assemble());
    }
}
