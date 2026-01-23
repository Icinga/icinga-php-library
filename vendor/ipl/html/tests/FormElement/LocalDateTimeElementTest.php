<?php

namespace ipl\Tests\Html;

use DateTime;
use ipl\Html\FormElement\LocalDateTimeElement;

class LocalDateTimeElementTest extends TestCase
{
    public function testRendersValueAttributeAsString()
    {
        $element = new LocalDateTimeElement('test');
        $element->setValue(DateTime::createFromFormat(LocalDateTimeElement::FORMAT, '2021-02-10T16:00:00'));

        $this->assertHtml(
            '<input type="datetime-local" step="1" name="test" value="2021-02-10T16:00:00">',
            $element
        );
    }

    public function testReturnsADatTimeObjectOnGetValue()
    {
        $element = new LocalDateTimeElement('test');
        $element->setValue('2021-02-10T16:00:00');

        $this->assertInstanceOf('DateTime', $element->getValue());
    }

    /**
     * @depends testReturnsADatTimeObjectOnGetValue
     */
    public function testValueAttributeHoldsAStringValue()
    {
        $this->markTestSkipped('Requires https://github.com/Icinga/ipl-html/pull/3');

        $element = new LocalDateTimeElement('test');
        $element->setValue('2021-02-10T16:00:00');

        $this->assertEquals('2021-02-10T16:00:00', $element->getAttributes()->get('value')->getValue());
    }
}
