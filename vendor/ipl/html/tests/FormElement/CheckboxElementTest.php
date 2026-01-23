<?php

namespace ipl\Tests\Html;

use ipl\Html\FormElement\CheckboxElement;
use ipl\I18n\NoopTranslator;
use ipl\I18n\StaticTranslator;

class CheckboxElementTest extends TestCase
{
    protected function setUp(): void
    {
        StaticTranslator::$instance = new NoopTranslator();
    }

    public function testRendersCheckValuesAsValueAttribute()
    {
        $checkbox = new CheckboxElement('test');

        $this->assertHtml(
            '<input type="hidden" name="test" value="n">'
            . '<input type="checkbox" name="test" value="y">',
            $checkbox
        );
    }

    public function testElementValueBehavesCoherentlyWithNonCheckboxes()
    {
        $checkbox = new CheckboxElement('test');

        // A non submitted checkbox has no value
        $this->assertNull($checkbox->getValue());

        // A checked checkbox submit populates the checked value as value
        $checkbox->setValue($checkbox->getCheckedValue());
        $this->assertEquals($checkbox->getCheckedValue(), $checkbox->getValue());

        // An unchecked checkbox submit populates the unchecked value as value
        $checkbox->setValue($checkbox->getUncheckedValue());
        $this->assertEquals($checkbox->getUncheckedValue(), $checkbox->getValue());
    }

    public function testIsCheckedReturnsFalseByDefault()
    {
        $checkbox = new CheckboxElement('test');

        $this->assertFalse($checkbox->isChecked());
    }

    public function testIsCheckedReturnsFalseIfValueDoesNotMatchCheckedValue()
    {
        $checkbox = new CheckboxElement('test');

        $checkbox->setValue('noop');
        $this->assertFalse($checkbox->isChecked());
    }

    public function testIsCheckedReturnsTrueIfValueMatchesCheckedValue()
    {
        $checkbox = new CheckboxElement('test');

        $checkbox->setValue($checkbox->getCheckedValue());
        $this->assertTrue($checkbox->isChecked());
    }

    public function testSetValueAcceptsBooleans()
    {
        $checkbox = new CheckboxElement('test');

        $checkbox->setValue(true);
        $this->assertTrue($checkbox->isChecked());
        $this->assertEquals($checkbox->getCheckedValue(), $checkbox->getValue());

        $checkbox->setValue(false);
        $this->assertFalse($checkbox->isChecked());
        $this->assertEquals($checkbox->getUncheckedValue(), $checkbox->getValue());
    }

    public function testRendersCheckedAttributeIfIsChecked()
    {
        $checkbox = new CheckboxElement('test');

        $checkbox->setChecked(true);
        $this->assertHtml(
            '<input type="hidden" name="test" value="n">'
            . '<input checked="checked" type="checkbox" name="test" value="y">',
            $checkbox
        );
    }

    public function testSetCheckValues()
    {
        $checkbox = new CheckboxElement('test');
        $checkedValue = 'checked';
        $unCheckedValue = 'unchecked';

        $checkbox->setCheckedValue($checkedValue);
        $checkbox->setUncheckedValue($unCheckedValue);
        $this->assertSame($checkedValue, $checkbox->getCheckedValue());
        $this->assertSame($unCheckedValue, $checkbox->getUncheckedValue());
        $this->assertHtml(
            '<input type="hidden" name="test" value="unchecked">'
            . '<input type="checkbox" name="test" value="checked">',
            $checkbox
        );

        $this->assertFalse($checkbox->isChecked());
        $checkbox->setValue($checkedValue);
        $this->assertTrue($checkbox->isChecked());
        $this->assertHtml(
            '<input type="hidden" name="test" value="unchecked">'
            . '<input checked="checked" type="checkbox" name="test" value="checked">',
            $checkbox
        );
    }

    public function testCheckboxNotValidIfRequiredAndUnchecked()
    {
        $checkbox = new CheckboxElement('test');
        $checkbox->setRequired();
        $checkbox->setChecked(false);

        $this->assertFalse($checkbox->isValid());
    }
}
