<?php

namespace ipl\Tests\Html;

use ipl\Html\Form;
use ipl\Tests\Html\TestDummy\PositionedFormElementDecorator;
use ipl\Tests\Html\TestDummy\SimpleFormElementDecorator;
use ipl\Tests\Html\TestDummy\WithinContainerFormElementDecorator;

class FormElementDecoratorTest extends TestCase
{
    public function testPositionedFormElementDecorator()
    {
        $form = (new Form())
            ->setDefaultElementDecorator(new PositionedFormElementDecorator())
            ->addElement('text', 'decorated-form-element');

        $html = <<<'HTML'
<form method="POST">
  <div class="positioned-decorator">
    <input type="text" name="decorated-form-element">
  </div>
</form>
HTML;

        $this->assertHtml($html, $form);
    }

    public function testSimpleFormElementDecorator()
    {
        $form = (new Form())
            ->setDefaultElementDecorator(new SimpleFormElementDecorator())
            ->addElement('text', 'decorated-form-element');

        $html = <<<'HTML'
<form method="POST">
  <div class="simple-decorator">
    <input type="text" name="decorated-form-element">
  </div>
</form>
HTML;

        $this->assertHtml($html, $form);
    }

    public function testWithinContainerFormElementDecorator()
    {
        $form = (new Form())
            ->setDefaultElementDecorator(new WithinContainerFormElementDecorator())
            ->addElement('text', 'decorated-form-element');

        $html = <<<'HTML'
<form method="POST">
  <div class="within-container-decorator">
    <div class="container">
      <input type="text" name="decorated-form-element">
    </div>
  </div>
</form>
HTML;

        $this->assertHtml($html, $form);
    }
}
