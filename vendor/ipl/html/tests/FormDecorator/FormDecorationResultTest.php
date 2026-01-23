<?php

namespace ipl\Tests\Html\FormDecorator;

use ipl\Html\Form;
use ipl\Html\FormDecoration\FormDecorationResult;
use ipl\Html\Html;
use ipl\Tests\Html\TestCase;

class FormDecorationResultTest extends TestCase
{
    public function testMethodAppend(): void
    {
        $form = $this->createMock(Form::class);
        $form->expects($this->exactly(3))->method('addHtml');
        $result = new FormDecorationResult($form);

        $result
            ->append(Html::tag('div', ['class' => 'first'], 'First'))
            ->append(Html::tag('div', ['class' => 'second'], 'Second'))
            ->append(Html::tag('div', ['class' => 'third'], 'Third'));
    }

    public function testMethodPrepend(): void
    {
        $form = $this->createMock(Form::class);
        $form->expects($this->exactly(3))->method('prependHtml');
        $result = new FormDecorationResult($form);

        $result
            ->prepend(Html::tag('div', ['class' => 'first'], 'First'))
            ->prepend(Html::tag('div', ['class' => 'second'], 'Second'))
            ->prepend(Html::tag('div', ['class' => 'third'], 'Third'));
    }

    public function testMethodWrap(): void
    {
        $form = new Form();
        $result = new FormDecorationResult($form);

        $result
            ->wrap(Html::tag('div', ['class' => 'wrapper-1']))
            ->wrap(Html::tag('div', ['class' => 'wrapper-2']))
            ->wrap(Html::tag('div', ['class' => 'wrapper-3']));

        $html = <<<'HTML'
<div class="wrapper-3">
  <div class="wrapper-2">
    <div class="wrapper-1">
      <form method="POST"></form>
    </div>
  </div>
</div>
HTML;

        $this->assertHtml($html, $form);
    }

    public function testAppendAfterWrap(): void
    {
        $form = new Form();
        $result = new FormDecorationResult($form);

        $result
            ->append(Html::tag('div', ['class' => 'first'], 'First'))
            ->wrap(Html::tag('div', ['class' => 'wrapper']))
            ->append(Html::tag('div', ['class' => 'second'], 'Second'));

        $html = <<<'HTML'
<div class="wrapper">
  <form method="POST">
    <div class="first">First</div>
  </form>
  <div class="second">Second</div>
</div>
HTML;

        $this->assertHtml($html, $form);
    }

    public function testPrependAfterWrap(): void
    {
        $form = new Form();
        $result = new FormDecorationResult($form);

        $result
            ->append(Html::tag('div', ['class' => 'first'], 'First'))
            ->wrap(Html::tag('div', ['class' => 'wrapper']))
            ->prepend(Html::tag('div', ['class' => 'second'], 'Second'));

        $html = <<<'HTML'
<div class="wrapper">
  <div class="second">Second</div>
  <form method="POST">
    <div class="first">First</div>
  </form>
</div>
HTML;
        $this->assertHtml($html, $form);
    }

    public function testMixed(): void
    {
        $form = new Form();
        $result = new FormDecorationResult($form);

        $result
            ->append(Html::tag('div', ['id' => '1']))
            ->wrap(Html::tag('div', ['id' => '2']))
            ->prepend(Html::tag('div', ['id' => '3']))
            ->wrap(Html::tag('div', ['id' => '4']))
            ->append(Html::tag('div', ['id' => '5']))
            ->prepend(Html::tag('div', ['id' => '6']))
            ->wrap(Html::tag('div', ['id' => '7']))
            ->wrap(Html::tag('div', ['id' => '8']))
            ->prepend(Html::tag('div', ['id' => '9']))
            ->prepend(Html::tag('div', ['id' => '10']))
            ->wrap(Html::tag('div', ['id' => '11']))
            ->append(Html::tag('div', ['id' => '12']))
            ->prepend(Html::tag('div', ['id' => '13']))
            ->append(Html::tag('div', ['id' => '14']));

        $html = <<<'HTML'
<div id="11">
  <div id="13"></div>
  <div id="8">
    <div id="10"></div>
    <div id="9"></div>
    <div id="7">
    <div id="4">
      <div id="6"></div>
      <div id="2">
        <div id="3"></div>
        <form method="POST">
          <div id="1"></div>
        </form>
      </div>
      <div id="5"></div>
    </div>
   </div>
  </div>
  <div id="12"></div>
  <div id="14"></div>
</div>
HTML;
        $this->assertHtml($html, $form);
    }
}
