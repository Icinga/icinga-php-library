<?php

namespace ipl\Tests\Html\FormElement;

use ipl\Html\Form;
use ipl\Html\FormElement\FieldsetElement;
use ipl\Html\FormElement\TextElement;
use ipl\I18n\NoopTranslator;
use ipl\I18n\StaticTranslator;
use ipl\Tests\Html\TestCase;
use ipl\Validator\CallbackValidator;

class FormElementValidationTest extends TestCase
{
    protected function setUp(): void
    {
        StaticTranslator::$instance = new NoopTranslator();
    }

    public function testRequiredElementsAreInvalidIfValidatedIndividually()
    {
        $element = new TextElement('test_element', ['required' => true]);

        $this->assertFalse($element->isValid(), 'Required form elements do not validate themselves correctly');
    }

    public function testRequiredElementsInFieldsetsAreRequired()
    {
        $fieldset = new FieldsetElement('test');
        $fieldset->addElement('text', 'tset', [
            'required' => true
        ]);

        $this->assertFalse($fieldset->isValid(), 'Required fieldset fields are not required');
    }

    public function testFormIsAbleToInvalidateElementValidationResults()
    {
        $form = new Form();
        $form->addElement('text', 'test', [
            'validators' => [new CallbackValidator(function ($value) {
                return $value === 'correct';
            })]
        ]);

        $form->populate(['test' => 'incorrect']);

        $this->assertFalse($form->isValid(), 'Broken prerequisite. A form is not invalid although its element is');

        // This cannot happen for standard form requests, but Form::validate() must
        // invalidate an element's validation state as it does invalidate its own
        $form->populate(['test' => 'correct']);
        $form->validate();

        $this->assertTrue($form->isValid(), 'A form is not valid although the error has been corrected');
    }

    public function testElementsAreAssembledForValidation()
    {
        $fieldset = new class ('test') extends FieldsetElement {
            protected function assemble()
            {
                $this->addElement('text', 'test', [
                    'validators' => [new CallbackValidator(function ($value) {
                        return $value === 'correct';
                    })]
                ]);
            }
        };

        $form = new class ($fieldset) extends Form {
            private $element;

            public function __construct($element)
            {
                $this->element = $element;
            }

            protected function assemble()
            {
                $this->addElement($this->element);
            }
        };

        $form->populate(['test' => ['test' => 'incorrect']]);

        $this->assertFalse($form->isValid(), 'A form is not invalid although its element is');
    }

    public function testValidationMessagesAreNotDuplicated()
    {
        $element = new TextElement('test_element', [
            'value' => 'bogus',
            'validators' => [new CallbackValidator(function ($value, $validator) {
                $validator->addMessage('This message should appear only once');
                return false;
            })]
        ]);

        // Multiple calls to `validate()` don't happen normally, but are technically possible
        // and the preferred solution to invalidate previous validation results
        $element->validate();
        $element->validate();

        $this->assertCount(
            1,
            $element->getMessages(),
            'FormElement validation messages are duplicated'
        );
    }

    public function testElementsAreOnlyValidatedIfTheyHaveAValueIfValidatedPartially()
    {
        $form = new class extends Form {
            protected function assemble()
            {
                $this->addElement('text', 'test', [
                    'required' => true
                ]);
            }
        };

        $form->validatePartial();

        $this->assertEmpty(
            $form->getElement('test')->getMessages(),
            'Empty elements are validated during partial validation'
        );
    }
}
