<?php

namespace ipl\Tests\Html\FormElement;

use InvalidArgumentException;
use ipl\Html\Attributes;
use ipl\Html\Form;
use ipl\Html\FormElement\RadioElement;
use ipl\Html\FormElement\RadioOption;
use ipl\I18n\NoopTranslator;
use ipl\I18n\StaticTranslator;
use ipl\Tests\Html\TestCase;

class RadioElementTest extends TestCase
{
    public function testRendersElementCorrectly()
    {
        $radio = new RadioElement('test', [
            'label'     => 'Test',
            'options'   => [
                'foo' => 'Foo',
                'bar' => 'Bar',
                'yes' => 'Yes'
            ]
        ]);

        $html = <<<'HTML'
<label class="radio-label"><input value="foo" name="test" type="radio">Foo</label>
<label class="radio-label"><input value="bar" name="test" type="radio">Bar</label>
<label class="radio-label"><input value="yes" name="test" type="radio">Yes</label>
HTML;
        $this->assertHtml($html, $radio);
    }

    public function testNumbersOfTypeIntOrStringAsOptionKeysAreHandledEqually()
    {
        $radio = new RadioElement('test', [
            'label'     => 'Test',
            'options'   => [
                '1' => 'Foo',
                2   => 'Bar',
                3   => 'Yes'
            ]
        ]);

        $radio->setValue(2);

        $html = <<<'HTML'
<label class="radio-label"><input value="1" name="test" type="radio">Foo</label>
<label class="radio-label"><input value="2" name="test" type="radio" checked>Bar</label>
<label class="radio-label"><input value="3" name="test" type="radio">Yes</label>
HTML;
        $this->assertHtml($html, $radio);

        $radio->setValue('2');
        $this->assertHtml($html, $radio);

        $radio->setDisabledOptions([2]);
        $this->assertTrue($radio->getOption(2)->isDisabled());

        $radio->setDisabledOptions(['2']);
        $this->assertTrue($radio->getOption(2)->isDisabled());

        $radio->setOptions([]);

        $radio->setDisabledOptions(['5']);

        $radio->setOptions(['5' => 'Five', 8 => 'Eight']);
        $this->assertTrue($radio->getOption(5)->isDisabled());

        $radio->getOption(5)->setDisabled(false);

        $radio->setDisabledOptions([5]);
        $this->assertTrue($radio->getOption(5)->isDisabled());
    }

    public function testSetValueAddsTheCheckedAttribute()
    {
        $radio = new RadioElement('test', [
            'label'     => 'Test',
            'options'   => [
                'foo' => 'Foo',
                'bar' => 'Bar',
                'yes' => 'Yes'
            ],
            'value'     => 'bar'
        ]);

        $html = <<<'HTML'
<label class="radio-label"><input value="foo" name="test" type="radio">Foo</label>
<label class="radio-label"><input value="bar" name="test" type="radio" checked>Bar</label>
<label class="radio-label"><input value="yes" name="test" type="radio">Yes</label>
HTML;
        $this->assertHtml($html, $radio);

        $radio->setValue('yes');

        $html = <<<'HTML'
<label class="radio-label"><input value="foo" name="test" type="radio">Foo</label>
<label class="radio-label"><input value="bar" name="test" type="radio">Bar</label>
<label class="radio-label"><input value="yes" name="test" type="radio" checked>Yes</label>
HTML;
        $this->assertHtml($html, $radio);

        $radio->setValue('no');

        $html = <<<'HTML'
<label class="radio-label"><input value="foo" name="test" type="radio">Foo</label>
<label class="radio-label"><input value="bar" name="test" type="radio">Bar</label>
<label class="radio-label"><input value="yes" name="test" type="radio">Yes</label>
HTML;
        $this->assertHtml($html, $radio);
    }

    public function testDisabledRadioOptions()
    {
        $radio = new RadioElement('test', [
            'label'             => 'Test',
            'disabledOptions'   => ['foo', 'bar', 'yes'],
            'options'           => [
                'foo' => 'Foo',
                'bar' => 'Bar',
                'yes' => 'Yes',
                'no'  => 'No'
            ]
        ]);

        $html = <<<'HTML'
<label class="radio-label"><input value="foo" name="test" type="radio" disabled>Foo</label>
<label class="radio-label"><input value="bar" name="test" type="radio" disabled>Bar</label>
<label class="radio-label"><input value="yes" name="test" type="radio" disabled>Yes</label>
<label class="radio-label"><input value="no" name="test" type="radio">No</label>
HTML;
        $this->assertHtml($html, $radio);

        $radio = new RadioElement('test', [
            'label'     => 'Test',
            'options'   => [
                'foo' => 'Foo',
                'bar' => 'Bar',
                'yes' => 'Yes',
                'no'  => 'No'
            ],
            'value'     => 'bar'
        ]);

        $radio->getOption('yes')->setDisabled();

        $html = <<<'HTML'
<label class="radio-label"><input value="foo" name="test" type="radio">Foo</label>
<label class="radio-label"><input value="bar" name="test" type="radio" checked>Bar</label>
<label class="radio-label"><input value="yes" name="test" type="radio" disabled>Yes</label>
<label class="radio-label"><input value="no" name="test" type="radio">No</label>
HTML;
        $this->assertHtml($html, $radio);

        $radio->setDisabledOptions(['no', 'foo']);

        $html = <<<'HTML'
<label class="radio-label"><input value="foo" name="test" type="radio" disabled>Foo</label>
<label class="radio-label"><input value="bar" name="test" type="radio" checked>Bar</label>
<label class="radio-label"><input value="yes" name="test" type="radio">Yes</label>
<label class="radio-label"><input value="no" name="test" type="radio" disabled>No</label>
HTML;
        $this->assertHtml($html, $radio);
    }

    public function testNonCallbackAttributesOfTheElementAreAppliedToEachOption()
    {
        $radio = new RadioElement('test', [
            'label'     => 'Test',
            'class'     => 'blue',
            'options'   => [
                'foo' => 'Foo',
                'bar' => 'Bar',
                'yes' => 'Yes',
                'no'  => 'No'
            ],
        ]);

        $radio->getAttributes()->add('data-js', 'radio');

        $html = <<<'HTML'
<label class="radio-label"><input value="foo" name="test" class="blue" data-js="radio" type="radio">Foo</label>
<label class="radio-label"><input value="bar" name="test" class="blue" data-js="radio" type="radio">Bar</label>
<label class="radio-label"><input value="yes" name="test" class="blue" data-js="radio" type="radio">Yes</label>
<label class="radio-label"><input value="no" name="test" class="blue" data-js="radio" type="radio">No</label>
HTML;
        $this->assertHtml($html, $radio);
    }

    public function testAddCssClassToTheLabelOfASpecificOption()
    {
        $radio = new RadioElement('test', [
            'label'     => 'Test',
            'options'   => [
                'foo' => 'Foo',
                'bar' => 'Bar',
                'yes' => 'Yes',
                'no'  => 'No'
            ],
        ]);

        $radio->getOption('bar')
            ->setLabelCssClass('new');

        $radio->getOption('yes')
            ->setLabelCssClass([RadioOption::LABEL_CLASS, 'new']);

        $html = <<<'HTML'
<label class="radio-label"><input value="foo" name="test" type="radio">Foo</label>
<label class="new"><input value="bar" name="test" type="radio">Bar</label>
<label class="radio-label new"><input value="yes" name="test" type="radio">Yes</label>
<label class="radio-label"><input value="no" name="test" type="radio">No</label>
HTML;
        $this->assertHtml($html, $radio);
    }

    public function testAddAttributesToASpecificOption()
    {
        $radio = new RadioElement('test', [
            'label'     => 'Test',
            'class'     => 'blue',
            'options'   => [
                'foo' => 'Foo',
                'bar' => 'Bar',
                'yes' => 'Yes',
                'no'  => 'No'
            ],
        ]);

        $radio->getOption('bar')
            ->addAttributes(Attributes::create(['class' => 'dark', 'id' => 'fav']));

        $html = <<<'HTML'
<label class="radio-label"><input value="foo" name="test" class="blue" type="radio">Foo</label>
<label class="radio-label"><input value="bar" name="test" class="blue dark" id="fav" type="radio">Bar</label>
<label class="radio-label"><input value="yes" name="test" class="blue" type="radio">Yes</label>
<label class="radio-label"><input value="no" name="test" class="blue" type="radio">No</label>
HTML;
        $this->assertHtml($html, $radio);
    }

    public function testRadioNotValidIfCheckedValueIsInvalid()
    {
        StaticTranslator::$instance = new NoopTranslator();
        $radio = new RadioElement('test', [
            'label'     => 'Test',
            'options'   => [
                'foo' => 'Foo',
                'bar' => 'Bar',
                'yes' => 'Yes'
            ],
            'value'     => 'bar'
        ]);

        $this->assertTrue($radio->isValid());

        $radio->setValue('no');
        $this->assertFalse($radio->isValid());

        $radio->setValue('tom');
        $this->assertFalse($radio->isValid());
    }

    public function testRadioNotValidIfCheckedValueIsDisabled()
    {
        StaticTranslator::$instance = new NoopTranslator();
        $radio = new RadioElement('test', [
            'label'     => 'Test',
            'options'   => [
                'foo' => 'Foo',
                'bar' => 'Bar',
                'yes' => 'Yes'
            ],
            'value'     => 'bar'
        ]);

        $radio->setValue('yes');

        $radio->getOption('yes')->setDisabled();
        $this->assertFalse($radio->isValid());
    }

    public function testNullAndTheEmptyStringValuesAreEquallyHandled()
    {
        $form = new Form();
        $form->addElement('radio', 'radio', [
            'options' => ['' => 'Please choose'],
            'value' => ''
        ]);
        $form->addElement('radio', 'radio2', [
            'options' => ['' => 'Please choose'],
            'value' => null
        ]);

        /** @var RadioElement $radio */
        $radio = $form->getElement('radio');
        /** @var RadioElement $radio2 */
        $radio2 = $form->getElement('radio2');

        $this->assertNull($radio->getValue());
        $this->assertNull($radio2->getValue());

        $this->assertInstanceOf(RadioOption::class, $radio->getOption(''));
        $this->assertInstanceOf(RadioOption::class, $radio2->getOption(''));

        $this->assertTrue($radio->isValid());
        $this->assertTrue($radio2->isValid());

        $radio->setValue(null);
        $this->assertTrue($radio->isValid());
        $radio2->setValue('');
        $this->assertTrue($radio2->isValid());

        $html = <<<'HTML'
<label class="radio-label"><input name="radio" type="radio" checked>Please choose</label>
HTML;

        $this->assertHtml($html, $radio);

        $html = <<<'HTML'
<label class="radio-label"><input name="radio2" type="radio" checked>Please choose</label>
HTML;

        $this->assertHtml($html, $radio2);

        $radio->setDisabledOptions([null, 'foo']);
        $radio2->setDisabledOptions(['', 'foo']);

        $html = <<<'HTML'
<label class="radio-label"><input name="radio" type="radio" disabled checked>Please choose</label>
HTML;

        $this->assertHtml($html, $radio);

        $html = <<<'HTML'
<label class="radio-label"><input name="radio2" type="radio" disabled checked>Please choose</label>
HTML;

        $this->assertHtml($html, $radio2);

        $radio->setValue(null);
        $this->assertNull($radio->getValue());

        $radio2->setValue('');
        $this->assertNull($radio2->getValue());
    }

    public function testSetOptionsResetsOptions()
    {
        $radio = new RadioElement('radio');
        $radio->setOptions(['foo' => 'Foo', 'bar' => 'Bar']);

        $this->assertInstanceOf(RadioOption::class, $radio->getOption('foo'));
        $this->assertInstanceOf(RadioOption::class, $radio->getOption('bar'));

        $radio->setOptions(['car' => 'Car', 'train' => 'Train']);

        $this->assertInstanceOf(RadioOption::class, $radio->getOption('car'));
        $this->assertInstanceOf(RadioOption::class, $radio->getOption('train'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('There is no such option "foo"');
        $radio->getOption('foo');
    }

    public function testOrderOfOptionsAndDisabledOptionsDoesNotMatter()
    {
        $radio = new RadioElement('test', [
            'label'             => 'Test',
            'options'           => [
                'foo' => 'Foo',
                'bar' => 'Bar'
            ],
            'disabledOptions'   => ['foo', 'bar']
        ]);

        $html = <<<'HTML'
<label class="radio-label"><input value="foo" name="test" type="radio" disabled>Foo</label>
<label class="radio-label"><input value="bar" name="test" type="radio" disabled>Bar</label>
HTML;
        $this->assertHtml($html, $radio);

        $radio = new RadioElement('test', [
            'disabledOptions'   => ['foo', 'bar'],
            'label'             => 'Test',
            'options'           => [
                'foo' => 'Foo',
                'bar' => 'Bar'
            ]
        ]);

        $this->assertHtml($html, $radio);
    }

    public function testGetOptionReturnsPreviouslySetOption()
    {
        $radio = new RadioElement('radio');
        $radio->setOptions(['' => 'Empty String', 'foo' => 'Foo', 'bar' => 'Bar']);

        $this->assertSame('', $radio->getOption('')->getValue());
        $this->assertSame('Empty String', $radio->getOption('')->getLabel());

        $this->assertSame('foo', $radio->getOption('foo')->getValue());
        $this->assertSame('Foo', $radio->getOption('foo')->getLabel());

        $radio->setOptions(['' => 'Please Choose', 'car' => 'Car', 'train' => 'Train']);

        $this->assertSame('', $radio->getOption('')->getValue());
        $this->assertSame('Please Choose', $radio->getOption('')->getLabel());

        $this->assertSame('car', $radio->getOption('car')->getValue());
        $this->assertSame('Car', $radio->getOption('car')->getLabel());
    }

    public function testNullAndTheEmptyStringAreEquallyHandledWhileDisablingOptions()
    {
        $radio = new RadioElement('radio');
        $radio->setOptions(['' => 'Foo', 'bar' => 'Bar']);
        $radio->setDisabledOptions([null]);

        $this->assertTrue($radio->getOption('')->isDisabled());

        $radio = new RadioElement('radio');
        $radio->setOptions(['' => 'Foo', 'bar' => 'Bar']);
        $radio->setDisabledOptions(['']);

        $this->assertTrue($radio->getOption('')->isDisabled());
    }

    public function testIsDecoratedWithLabelAndDescription(): void
    {
        $radio = new RadioElement('radio', ['id' => 'radio']);
        $radio->setLabel('Radio');
        $radio->setDescription('Description');
        $radio->setOptions(['a' => 'A', 'b' => 'B']);
        $radio->setDecorators(['Label', 'RenderElement', 'Description']);

        $html = <<<'HTML'
<label class="form-element-label">Radio</label>
<label class="radio-label"><input aria-describedby="desc_radio" value="a" name="radio" type="radio">A</label>
<label class="radio-label"><input aria-describedby="desc_radio" value="b" name="radio" type="radio">B</label>
<p class="form-element-description" id="desc_radio">Description</p>
HTML;

        $radio->applyDecoration();
        $this->assertHtml($html, $radio);
    }

    public function testSetDisabledOptionsResetsDisabledOptions()
    {
        $radio = new RadioElement('test', [
            'options'   => [
                '1' => 'Foo',
                2   => 'Bar',
                3   => 'Yes'
            ]
        ]);

        $radio->setDisabledOptions(['1']);
        $this->assertTrue($radio->getOption('1')->isDisabled());
        $this->assertFalse($radio->getOption(2)->isDisabled());
        $this->assertFalse($radio->getOption(3)->isDisabled());

        $radio->setDisabledOptions([3]);
        $this->assertFalse($radio->getOption('1')->isDisabled());
        $this->assertFalse($radio->getOption(2)->isDisabled());
        $this->assertTrue($radio->getOption(3)->isDisabled());

        $radio->setDisabledOptions([]);
        $this->assertFalse($radio->getOption('1')->isDisabled());
        $this->assertFalse($radio->getOption(2)->isDisabled());
        $this->assertFalse($radio->getOption(3)->isDisabled());
    }


    public function testEmptyStringOptionCannotBeDisabledByFalsyValuesExceptNullOrEmptyString()
    {
        $radio = new RadioElement('test', [
            'options' => [
                ''      => 'Option to disable',
                'foo'   => 'Foo'
            ]
        ]);

        $radio->setDisabledOptions([false, 0, [], 0.0]);
        $this->assertFalse($radio->getOption('')->isDisabled());

        $radio->setDisabledOptions([null]);
        $this->assertTrue($radio->getOption('')->isDisabled());

        $radio->setDisabledOptions(['']);
        $this->assertTrue($radio->getOption('')->isDisabled());
    }

    public function testEmptyStringOptionCannotBeCheckedByFalsyValuesExceptNullOrEmptyString()
    {
        $radio = new RadioElement('test', [
            'options' => [
                ''      => 'Option to check',
                'foo'   => 'Foo'
            ]
        ]);

        $radio->setValue(false);
        $this->assertStringNotContainsString('checked', $radio->render());

        $radio->setValue(0);
        $this->assertStringNotContainsString('checked', $radio->render());

        $radio->setValue([]);
        $this->assertStringNotContainsString('checked', $radio->render());

        $radio->setValue(0.0);
        $this->assertStringNotContainsString('checked', $radio->render());

        $radio->setValue('');
        $this->assertStringContainsString('checked', $radio->render());

        $radio->setValue(null);
        $this->assertStringContainsString('checked', $radio->render());
    }

    public function testDisabledOptionsCanBePreSet()
    {
        $radio = new RadioElement('test', [
            'disabledOptions' => ['foo']
        ]);
        $radio->setOptions(['foo' => 'Foo', 'bar' => 'Bar']);

        $this->assertTrue($radio->getOption('foo')->isDisabled());
        $this->assertFalse($radio->getOption('bar')->isDisabled());
    }

    public function testGetOptionThrowsExceptionIfOptionDoesNotExist()
    {
        $radio = new RadioElement('test');
        $radio->setOptions(['foo' => 'Foo']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('There is no such option "bar"');
        $radio->getOption('bar');
    }

    public function testAttributesAreAppliedToAllOptions()
    {
        $radio = new RadioElement('test', [
            'options' => ['foo' => 'Foo', 'bar' => 'Bar'],
            'class'   => 'my-radio'
        ]);

        $html = $radio->render();
        $this->assertStringContainsString('class="my-radio"', $html);
        $this->assertSame(2, substr_count($html, 'class="my-radio"'));
    }
}
