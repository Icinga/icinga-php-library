<?php

namespace ipl\Tests\Html\FormDecorator;

use ipl\Html\Contract\DecorationResult;
use ipl\Html\Contract\Form;
use ipl\Html\Contract\FormDecoration;
use ipl\Html\Contract\FormElement;
use ipl\Html\Contract\FormElementDecoration;
use ipl\Html\FormDecoration\Transformation;
use ipl\Html\Html;
use ipl\Tests\Html\TestCase;

class FormDecorationTest extends TestCase
{
    private function createFormDecorator()
    {
        return new class implements FormDecoration, FormElementDecoration {
            private ?Transformation $transformation = null;

            public function setTransformation(Transformation $transformation): static
            {
                $this->transformation = $transformation;

                return $this;
            }

            public function decorateForm(DecorationResult $result, Form $form): void
            {
                $this->transformation->apply($result, Html::tag('div', ['class' => 'decorator-result']));
            }

            public function decorateFormElement(DecorationResult $result, FormElement $formElement): void
            {
                $result->wrap(Html::tag('div', ['class' => 'has-message']));
            }
        };
    }

    public function testPrependingContentWorks(): void
    {
        $form = new \ipl\Html\Form();
        $form->addHtml(Html::tag('div', ['class' => 'test-html']));
        $form->getDecorators()->addDecorator(
            'test',
            $this->createFormDecorator()
                ->setTransformation(Transformation::Prepend)
        );

        $this->assertHtml(
            '<form method="POST"><div class="decorator-result"></div><div class="test-html"></div></form>',
            $form
        );
    }

    public function testFormDecorationWithFormElement(): void
    {
        $form = new \ipl\Html\Form();
        $form->addElementDecoratorLoaderPaths([[__NAMESPACE__, 'Decorator']]);
        $form->getDecorators()->addDecorator(
            'test',
            $this->createFormDecorator()
                ->setTransformation(Transformation::Prepend)
        );
        $form->addElement('text', 'test', [
            'decorators' => ['TestRenderElement', 'test' => $this->createFormDecorator()]
        ]);

        $html = <<<'HTML'
<form method="POST">
    <div class="decorator-result"></div>
    <div class="has-message">
        <input type="text" name="test" />
    </div>
</form>
HTML;

        $this->assertHtml($html, $form);
    }

    public function testAppendingContentWorks(): void
    {
        $form = new \ipl\Html\Form();
        $form->addHtml(Html::tag('div', ['class' => 'test-html']));
        $form->getDecorators()->addDecorator(
            'test',
            $this->createFormDecorator()
                ->setTransformation(Transformation::Append)
        );

        $this->assertHtml(
            '<form method="POST"><div class="test-html"></div><div class="decorator-result"></div></form>',
            $form
        );
    }

    public function testWrappingContentWorks(): void
    {
        $form = new \ipl\Html\Form();
        $form->addHtml(Html::tag('div', ['class' => 'test-html']));
        $form->getDecorators()->addDecorator(
            'test',
            $this->createFormDecorator()
                ->setTransformation(Transformation::Wrap)
        );

        $this->assertHtml(
            '<div class="decorator-result"><form method="POST"><div class="test-html"></div></form></div>',
            $form
        );
    }

    public function testFormDecorationWithASingletonDecorator(): void
    {
        $singleton = $this->createMockForIntersectionOfInterfaces([
            FormDecoration::class,
            FormElementDecoration::class,
        ]);

        $singleton->expects($this->once())->method('decorateForm');
        $singleton->expects($this->once())->method('decorateFormElement');

        $form = new \ipl\Html\Form();
        $form->getDecorators()->addDecorator('test', $singleton);
        $form->addElement('text', 'test', [
            'decorators' => ['test' => $singleton]
        ]);

        $form->render();
    }

    public function testFormDecorationIsDoneOnlyOnce(): void
    {
        $form = new \ipl\Html\Form();
        $form->getDecorators()->addDecorator(
            'test',
            $this->createFormDecorator()
                ->setTransformation(Transformation::Append)
        );

        $form->render();

        $html = <<<'HTML'
<form method="POST">
    <div class="decorator-result"></div>
</form>
HTML;

        $this->assertHtml($html, $form);
    }

    /**
     * @depends testFormDecorationWithASingletonDecorator
     * @depends testFormDecorationWithFormElement
     */
    public function testFormDecorationIsDoneAfterElementDecoration(): void
    {
        $decorator = new class implements FormDecoration, FormElementDecoration {
            public array $calls = [];

            public function decorateForm(DecorationResult $result, Form $form): void
            {
                $this->calls[] = 'form';
            }

            public function decorateFormElement(DecorationResult $result, FormElement $formElement): void
            {
                $this->calls[] = 'element';
            }
        };

        $form = new \ipl\Html\Form();
        $form->getDecorators()->addDecorator('test', $decorator);
        $form->addElement('text', 'test', [
            'decorators' => ['test' => $decorator]
        ]);

        $form->render();

        $this->assertSame(['element', 'form'], $decorator->calls);
    }
}
