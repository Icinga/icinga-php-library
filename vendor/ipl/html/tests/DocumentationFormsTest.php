<?php

namespace ipl\Tests\Html;

use ipl\Html\Form;
use ipl\Html\FormElement\TextElement;
use ipl\Html\Html;

class DocumentationFormsTest extends TestCase
{
    public function testFirstForm()
    {
        $form = new Form();
        $form->setAction('/your/url');
        $form->addElement('text', 'name', ['label' => 'Your name']);
        $this->assertHtml(
            '<form action="/your/url" method="POST"><input name="name" type="text" /></form>',
            $form
        );
    }

    public function testSelectElement()
    {
        $form = new Form();
        $form->addElement('select', 'customer', [
            'label'   => 'Customer',
            'options' => [
                ''   => 'Please choose',
                '1'  => 'The one',
                '4'  => 'Four',
                '5'  => 'Hi five',
            ],
        ]);

        $form->getElement('customer')
            ->addAttributes([
                'class' => ['important', 'customer'],
                'id'    => 'customer-field'
            ]);

        $this->assertHtml(
            '<select name="customer" class="important customer" id="customer-field">'
            . '<option value="" selected="selected">Please choose</option>'
            . '<option value="1">The one</option>'
            . '<option value="4">Four</option>'
            . '<option value="5">Hi five</option>'
            . '</select>',
            $form->getElement('customer')
        );
    }

    public function testSetValues()
    {
        $form = new Form();
        $form->setAction('/your/url');
        $form->addElement('text', 'name', ['label' => 'Your name']);
        $form->addElement('select', 'customer', [
            'label'   => 'Customer',
            'options' => [
                ''   => 'Please choose',
                '1'  => 'The one',
                '4'  => 'Four',
                '5'  => 'Hi five',
            ],
        ]);

        $form->populate([
            'name'     => 'John Doe',
            'customer' => '5'
        ]);
        $form->getElement('customer')->setValue('4');

        $this->assertEquals([
            'name'     => 'John Doe',
            'customer' => '4'
        ], $form->getValues());
    }

    public function testManuallyCreateTextElement()
    {
        $form = new Form();
        $nameElement = new TextElement('name', ['class' => 'important']);
        $form->addElement($nameElement);

        $this->assertHtml(
            '<form method="POST"><input type="text" name="name" class="important" />'
            . '</form>',
            $form
        );
    }

    public function testCustomHtml()
    {
        $form = new Form();

        $this->assertHtml(
            '<form method="POST"><div><input name="first_name" type="text" /><br />'
            . '<input name="last_name" type="text" /></div></form>',
            $form
            ->registerElement($form->createElement('text', 'first_name'))
            ->registerElement($form->createElement('text', 'last_name'))
            ->add(Html::tag('div', [
                $form->getElement('first_name'),
                Html::tag('br'),
                $form->getElement('last_name'),
            ]))
        );
    }
}
