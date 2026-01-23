<?php

namespace ipl\Tests\Html\FormElement;

use ipl\Html\Form;
use ipl\Html\FormElement\SubmitButtonElement;
use ipl\Tests\Html\TestCase;

class SubmitButtonElementTest extends TestCase
{
    public function testSubmitButtonIsOnlyPressedIfPopulated()
    {
        $button = new SubmitButtonElement('test', [
            'label' => 'Test'
        ]);

        $this->assertFalse($button->hasBeenPressed(), 'Submit buttons seem to be always pressed');

        $form = new Form();
        $form->addElement('submitButton', 'test', [
            'label' => 'Test'
        ]);
        $form->addElement('submitButton', 'test2', [
            'label' => 'Test 2'
        ]);
        $form->populate(['test2' => 'y']);

        $this->assertFalse(
            $form->getElement('test')->hasBeenPressed(),
            'A submit button is pressed even if another one is'
        );
    }

    public function testSubmitButtonsWithSameNameCanBeDifferentiated()
    {
        $form = new class extends Form {
            protected function assemble()
            {
                $this->addElement('submitButton', 'a_button', [
                    'value' => 'a'
                ]);
                $this->addHtml($this->createElement('submitButton', 'a_button', [
                    'value' => 'b'
                ]));
            }
        };

        $form->populate(['a_button' => 'a']);
        $form->ensureAssembled(); // handleRequest usually assembles

        $this->assertSame(
            'a',
            $form->getValue('a_button'),
            'Multiple submit buttons with the same name cannot be differentiated by their value'
        );

        $html = <<<'HTML'
<form method="POST">
      <button name="a_button" type="submit" value="a"/>
      <button name="a_button" type="submit" value="b"/>
</form>
HTML;

        $this->assertHtml($html, $form);
    }
}
