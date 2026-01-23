<?php

namespace ipl\Tests\Html\FormDecorator;

use ipl\Html\FormDecoration\FormElementDecorationResult;
use ipl\Html\FormDecoration\Transformation;
use ipl\Html\Html;
use ipl\Tests\Html\TestCase;

class DecorationResultsTest extends TestCase
{
    protected FormElementDecorationResult $results;

    public function setUp(): void
    {
        $this->results = new FormElementDecorationResult();
    }

    public function testEmptyDecorationResultsRenderEmptyString(): void
    {
        $this->assertSame('', $this->results->assemble()->render());
    }

    public function testMethodAppend(): void
    {
        $this->results
            ->append(Html::tag('div', ['class' => 'first'], 'First'))
            ->append(Html::tag('div', ['class' => 'second'], 'Second'))
            ->append(Html::tag('div', ['class' => 'third'], 'Third'));

        $html = <<<'HTML'
<div class="first">First</div>
<div class="second">Second</div>
<div class="third">Third</div>
HTML;

        $this->assertHtml($html, $this->results->assemble());
    }

    public function testMethodPrepend(): void
    {
        $this->results
            ->prepend(Html::tag('div', ['class' => 'third'], 'Third'))
            ->prepend(Html::tag('div', ['class' => 'second'], 'Second'))
            ->prepend(Html::tag('div', ['class' => 'first'], 'First'));

        $html = <<<'HTML'
<div class="first">First</div>
<div class="second">Second</div>
<div class="third">Third</div>
HTML;

        $this->assertHtml($html, $this->results->assemble());
    }

    public function testMethodWrap(): void
    {
        $this->results
            ->wrap(Html::tag('div', ['class' => 'wrapper-1']))
            ->wrap(Html::tag('div', ['class' => 'wrapper-2']))
            ->wrap(Html::tag('div', ['class' => 'wrapper-3']));

        $html = <<<'HTML'
<div class="wrapper-3">
  <div class="wrapper-2">
    <div class="wrapper-1"></div>
  </div>
</div>
HTML;

        $this->assertHtml($html, $this->results->assemble());
    }

    public function testAppendAfterWrap(): void
    {
        $this->results
            ->append(Html::tag('div', ['class' => 'first'], 'First'))
            ->wrap(Html::tag('div', ['class' => 'wrapper']))
            ->append(Html::tag('div', ['class' => 'second'], 'Second'));

        $html = <<<'HTML'
<div class="wrapper">
  <div class="first">First</div>
</div>
<div class="second">Second</div>
HTML;

        $this->assertHtml($html, $this->results->assemble());
    }
    public function testPrependAfterWrap(): void
    {
        $this->results
            ->append(Html::tag('div', ['class' => 'first'], 'First'))
            ->wrap(Html::tag('div', ['class' => 'wrapper']))
            ->prepend(Html::tag('div', ['class' => 'second'], 'Second'));

        $html = <<<'HTML'
<div class="second">Second</div>
<div class="wrapper">
  <div class="first">First</div>
</div>
HTML;
        $this->assertHtml($html, $this->results->assemble());
    }

    public function testMixed(): void
    {
        $this->results
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
<div id="13"></div>
<div id="11">
  <div id="10"></div>
  <div id="9"></div>
  <div id="8">
   <div id="7">
    <div id="6"></div>
    <div id="4">
      <div id="3"></div>
      <div id="2">
        <div id="1"></div>
      </div>
    </div>
    <div id="5"></div>
   </div>
  </div>
</div>
<div id="12"></div>
<div id="14"></div>
HTML;
        $this->assertHtml($html, $this->results->assemble());
    }

    public function testMethodTransformSupportAllCasesAndDoNotThrowAnException(): void
    {
        foreach (Transformation::cases() as $transformation) {
            $transformation->apply($this->results, Html::tag('div'));
        }

        $this->assertNotEmpty($this->results->assemble()->render());
    }

    public function testMethodTransformResultsSameAsAppendPrependAndWrap(): void
    {
        $transform = new FormElementDecorationResult();

        $this->assertSame(
            $this->results->append(Html::tag('tag1'))->assemble()->render(),
            Transformation::Append->apply($transform, Html::tag('tag1'))->assemble()->render()
        );

        $this->assertSame(
            $this->results->wrap(Html::tag('tag2'))->assemble()->render(),
            Transformation::Wrap->apply($transform, Html::tag('tag2'))->assemble()->render()
        );

        $this->assertSame(
            $this->results->prepend(Html::tag('tag3'))->assemble()->render(),
            Transformation::Prepend->apply($transform, Html::tag('tag3'))->assemble()->render()
        );

        $this->assertSame(
            $this->results->append(Html::tag('tag4'))->assemble()->render(),
            Transformation::Append->apply($transform, Html::tag('tag4'))->assemble()->render()
        );
    }
}
