<?php

namespace ipl\Tests\Html\FormDecorator;

use ipl\Html\Contract\FormElement;
use ipl\Html\FormDecoration\FormElementDecorationResult;
use ipl\Html\FormDecoration\FieldsetDecorator;
use ipl\Html\FormElement\FieldsetElement;
use ipl\Html\FormElement\TextElement;
use ipl\Tests\Html\TestCase;

class FieldsetDecoratorTest extends TestCase
{
    protected FieldsetDecorator $decorator;

    public function setUp(): void
    {
        $this->decorator = new FieldsetDecorator();
    }

    public function testWithDescriptionAttributeOnly(): void
    {
        $fieldset = new FieldsetElement('test', ['description' => 'Testing']);
        $this->decorator->decorateFormElement(new FormElementDecorationResult(), $fieldset);

        $html = <<<HTML
<fieldset name="test">
  <p>Testing</p>
</fieldset>
HTML;

        $this->assertHtml($html, $fieldset);
    }

    public function testWithLabelAttributeOnly(): void
    {
        $fieldset = new FieldsetElement('test', ['label' => 'Testing']);
        $this->decorator->decorateFormElement(new FormElementDecorationResult(), $fieldset);

        $html = <<<HTML
<fieldset name="test">
  <legend>Testing</legend>
</fieldset>
HTML;

        $this->assertHtml($html, $fieldset);
    }

    public function testWithLabelAndDescriptionAttributes(): void
    {
        $fieldset = new FieldsetElement('test', ['label' => 'Testing', 'description' => 'Testing']);
        $this->decorator->decorateFormElement(new FormElementDecorationResult(), $fieldset);

        $html = <<<HTML
<fieldset name="test">
  <legend>Testing</legend>
  <p>Testing</p>
</fieldset>
HTML;

        $this->assertHtml($html, $fieldset);
    }

    public function testWithoutLabelAndDescriptionAttributes(): void
    {
        $fieldset = new FieldsetElement('test');
        $this->decorator->decorateFormElement(new FormElementDecorationResult(), $fieldset);

        $html = <<<HTML
<fieldset name="test"></fieldset>
HTML;

        $this->assertHtml($html, $fieldset);
    }

    public function testOnlyFieldsetElementsAreDecorated(): void
    {
        $textElement = new TextElement('test', ['label' => 'Testing', 'description' => 'Testing']);
        $this->decorator->decorateFormElement(new FormElementDecorationResult(), $textElement);

        // no change applied by decorator
        $this->assertHtml('<input type="text" name="test">', $textElement);
    }

    public function testDecorationResultsRemainEmpty(): void
    {
        $results = new FormElementDecorationResult();
        $this->decorator->decorateFormElement($results, new FieldsetElement('test', ['label' => 'Testing']));
        $this->decorator->decorateFormElement($results, new FieldsetElement('test', ['label' => 'Testing']));
        $this->decorator->decorateFormElement($results, new FieldsetElement('test', ['label' => 'Testing']));

        $this->assertEmpty($results->assemble()->render());
    }

    public function testNonHtmlFormElementsAreIgnored(): void
    {
        $results = new FormElementDecorationResult();
        $element = $this->createStub(FormElement::class);

        $this->decorator->decorateFormElement($results, $element);

        $this->assertEmpty($results->assemble()->render());
    }
}
