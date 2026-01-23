<?php

namespace ipl\Tests\Html\FormElement;

use ipl\Html\Form;
use ipl\Html\FormDecorator\DivDecorator;
use ipl\Html\FormElement\FieldsetElement;
use ipl\I18n\NoopTranslator;
use ipl\I18n\StaticTranslator;
use ipl\Tests\Html\TestCase;
use ipl\Tests\Html\TestDummy\SimpleFormElementDecorator;
use ipl\Validator\CallbackValidator;
use LogicException;

class FieldsetElementTest extends TestCase
{
    private const SELECT_OPTIONS_TO_TEST = [
        ''    => 'Nothing',
        1     => 'One',
        'two' => 2,
    ];

    public function testElementLoading(): void
    {
        $form = (new Form())
            ->addElement('fieldset', 'test_fieldset');

        $this->assertInstanceOf(FieldsetElement::class, $form->getElement('test_fieldset'));
    }

    public function testGetValueWithDefaultWithoutNameThrowsException()
    {
        $this->expectException(LogicException::class);

        (new FieldsetElement('test_fieldset'))->getValue(null, 'default');
    }

    public function testOneDimension(): void
    {
        $fieldset = (new FieldsetElement('test_fieldset'))
            ->addElement('select', 'test_select', ['options' => self::SELECT_OPTIONS_TO_TEST])
            ->populate(['test_select' => 1]);

        $expected = <<<'HTML'
<fieldset name="test_fieldset">
  <select name="test_fieldset[test_select]">
    <option value="">Nothing</option>
    <option selected="selected" value="1">One</option>
    <option value="two">2</option>
  </select>
</fieldset>
HTML;

        $this->assertHtml($expected, $fieldset);
    }

    public function testTwoDimensions(): void
    {
        $inner = (new FieldsetElement('inner'))
            ->addElement('select', 'test_select', ['options' => self::SELECT_OPTIONS_TO_TEST]);
        $outer = (new FieldsetElement('outer'))
            ->addElement($inner)
            ->populate([
                'inner' => ['test_select' => 'two']
            ]);

        $expected = <<<'HTML'
<fieldset name="outer">
  <fieldset name="outer[inner]">
    <select name="outer[inner][test_select]">
      <option value="">Nothing</option>
      <option value="1">One</option>
      <option selected="selected" value="two">2</option>
    </select>
  </fieldset>
</fieldset>
HTML;

        $this->assertHtml($expected, $outer);
    }

    public function testMultipleDimensions(): void
    {
        $outer = new FieldsetElement('outer');

        foreach (static::SELECT_OPTIONS_TO_TEST as $key => $value) {
            $inner = (new FieldsetElement($key))
                ->addElement('select', 'test_select', [
                    'value'   => $value,
                    'options' => self::SELECT_OPTIONS_TO_TEST
                ]);
            $outer->addElement($inner);
        }

        $expected = <<<'HTML'
<fieldset name="outer">
  <fieldset name="outer[]">
    <select name="outer[][test_select]">
      <option value="">Nothing</option>
      <option value="1">One</option>
      <option value="two">2</option>
    </select>
  </fieldset>
  <fieldset name="outer[1]">
    <select name="outer[1][test_select]">
      <option value="">Nothing</option>
      <option value="1">One</option>
      <option value="two">2</option>
    </select>
  </fieldset>
  <fieldset name="outer[two]">
    <select name="outer[two][test_select]">
      <option value="">Nothing</option>
      <option value="1">One</option>
      <option value="two">2</option>
    </select>
  </fieldset>
</fieldset>
HTML;

        $this->assertHtml($expected, $outer);
    }

    public function testOriginalElementNames(): void
    {
        $inner = (new FieldsetElement('inner'))
            ->addElement('select', 'test_select');
        $outer = (new FieldsetElement('outer'))
            ->addElement($inner);

        $this->assertSame(
            'test_select',
            $outer->getElement('inner')->getElement('test_select')->getName()
        );
    }

    public function testDecoratorPropagation(): void
    {
        // Order is important because addElement() calls decorate(). Could be fixed.
        $fieldset = (new FieldsetElement('test_fieldset'));
        (new Form())
            ->setDefaultElementDecorator(new SimpleFormElementDecorator())
            ->addElement($fieldset);
        $fieldset->addElement('select', 'test_select');

        $expected = <<<'HTML'
<div class="simple-decorator">
  <fieldset name="test_fieldset">
    <div class="simple-decorator">
      <select name="test_fieldset[test_select]"></select>
    </div>
  </fieldset>
</div>
HTML;

        $this->assertHtml($expected, $fieldset);
    }

    public function testDecoratorPropagationWithExplicitDecorateCall(): void
    {
        $fieldset = new FieldsetElement('test_fieldset');

        (new SimpleFormElementDecorator())->decorate($fieldset);

        $fieldset->addElement('select', 'test_select');

        $expected = <<<'HTML'
<div class="simple-decorator">
  <fieldset name="test_fieldset">
    <div class="simple-decorator">
      <select name="test_fieldset[test_select]"></select>
    </div>
  </fieldset>
</div>
HTML;

        $this->assertHtml($expected, $fieldset);
    }

    public function testDecoratorPropagationWithDivDecorator(): void
    {
        // Order is important because addElement() calls decorate(). Could be fixed.
        $fieldset = (new FieldsetElement('test_fieldset'));
        $form = (new Form())
            ->setDefaultElementDecorator(new DivDecorator())
            // The div decorator adds attributes to itself during `decorate()`,
            // so we add an element before the fieldset for that to be called
            // and we can test if the propagation works with unbound clones of the decorator.
            ->addElement('input', 'before_fieldset')
            ->addElement($fieldset)
            ->addElement('input', 'after_fieldset');
        $fieldset->addElement('select', 'test_select');
        $fieldset->addElement('input', 'test_input');

        $expected = <<<'HTML'
<form method="POST">
  <div class="form-element">
    <input name="before_fieldset">
  </div>
  <div class="form-element">
    <fieldset name="test_fieldset">
      <div class="form-element">
        <select name="test_fieldset[test_select]"></select>
      </div>
      <div class="form-element">
        <input name="test_fieldset[test_input]"/>
      </div>
    </fieldset>
  </div>
  <div class="form-element">
    <input name="after_fieldset">
  </div>
</form>
HTML;

        $this->assertHtml($expected, $form);
    }

    public function testSupportsValidatorsLikeAnyOtherElement()
    {
        $fieldset = new FieldsetElement('test_fieldset', [
            'validators' => [new CallbackValidator(function () {
                return false;
            })]
        ]);
        $fieldset->addElement('text', 'test', [
            'value' => 'bogus'
        ]);

        $this->assertFalse($fieldset->isValid(), 'Fieldsets cannot validate themselves');
    }

    public function testDoesNotValidateItselfBeforeItsElementsAndOnlyIfThoseAreValid()
    {
        StaticTranslator::$instance = new NoopTranslator();

        $carry = null;
        $flag = null;

        $fieldset = new FieldsetElement('test_fieldset', [
            'validators' => [new CallbackValidator(function () use (&$carry, &$flag) {
                $flag = 'flag';

                return $carry !== null;
            })]
        ]);
        $fieldset->addElement('text', 'test', [
            'required'   => true,
            'validators' => [new CallbackValidator(function () use (&$carry) {
                $carry = 'carry';

                return true;
            })]
        ]);

        $fieldset->validate();

        $this->assertNull($flag, 'Fieldsets are validated even if their elements are invalid');

        $fieldset->populate(['test' => 'bogus'])
            ->validate();

        $this->assertTrue($fieldset->isValid(), 'Fieldsets are validated before their elements are');
    }

    public function testFieldsetsOnlyHaveAValueIfAnyOfItsElementsHaveOne()
    {
        $fieldset = (new FieldsetElement('test_fieldset'))
            ->addElement('text', 'test');

        $this->assertFalse($fieldset->hasValue(), 'Fieldsets with empty elements are not empty themselves');

        $fieldset->setValue(['test' => 'bogus']);

        $this->assertTrue($fieldset->hasValue(), 'Fieldsets with non-empty elements are not non-empty themselves');
    }

    public function testNestedFieldsetsAlsoOnlyHaveAValueIfOneOfTheirElementsHaveOne()
    {
        $fieldset = (new FieldsetElement('test_fieldset'))
            ->addElement('fieldset', 'nested_set');
        $fieldset->getElement('nested_set')
            ->addElement('text', 'test');

        $this->assertFalse($fieldset->hasValue(), 'Nested fieldsets with empty elements are not empty themselves');

        $fieldset->setValue(['nested_set' => ['test' => 'bogus']]);

        $this->assertTrue(
            $fieldset->hasValue(),
            'Nested fieldsets with non-empty elements are not non-empty themselves'
        );
    }

    public function testMultiSelectionFieldInFieldset(): void
    {
        $fieldset = (new FieldsetElement('test_fieldset'))
            ->addElement('select', 'test_select', [
                'multiple' => true,
                'options' => [
                    'one' => 'One',
                    'two' => 'Two'
                ]
            ]);

        $expected = <<<'HTML'
<fieldset name="test_fieldset">
    <select multiple="multiple" name="test_fieldset[test_select][]">
        <option value="one">One</option>
        <option value="two">Two</option>
    </select>
</fieldset>
HTML;

            $this->assertHtml($expected, $fieldset);
    }

    public function testMultiSelectionFieldInNestedFieldset(): void
    {
        $inner = (new FieldsetElement('inner'))
            ->addElement('select', 'test_select', [
                'multiple' => true,
                'options' => [
                    'one' => 'One',
                    'two' => 'Two'
                ]
            ]);
        $outer = (new FieldsetElement('outer'))
            ->addElement($inner);

        $expected = <<<'HTML'
<fieldset name="outer">
    <fieldset name="outer[inner]">
        <select multiple="multiple" name="outer[inner][test_select][]">
            <option value="one">One</option>
            <option value="two">Two</option>
        </select>
    </fieldset>
</fieldset>
HTML;

        $this->assertHtml($expected, $outer);
    }

    public function testFieldsetsAreAssembledWhenAskingForTheirValue(): void
    {
        $fieldset = new class ('set') extends FieldsetElement {
            protected function assemble()
            {
                $this->addElement('text', 'test');
            }
        };

        $fieldset->populate(['test' => 'bogus']);

        $this->assertTrue($fieldset->hasValue(), 'Fieldsets are not assembled when asked for their value');
    }
}
