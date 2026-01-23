<?php

namespace ipl\Tests\Html\FormDecorator;

use ipl\Html\Attributes;
use ipl\Html\Contract\DecorationResult;
use ipl\Html\Contract\FormElementDecoration;
use ipl\Html\Contract\DecoratorOptions;
use ipl\Html\Contract\DecoratorOptionsInterface;
use ipl\Html\Contract\FormElement;
use ipl\Html\HtmlElement;

/**
 * Wraps the $formElement with a div with class "test-with-options-decorator"
 *
 * Apply options using setter
 */
class TestWithOptionsDecorator implements FormElementDecoration, DecoratorOptionsInterface
{
    use DecoratorOptions;

    protected array $attrs = [];

    public function getName(): string
    {
        return 'TestWithOptions';
    }

    public function decorateFormElement(DecorationResult $result, FormElement $formElement): void
    {
        $result->wrap(new HtmlElement('div', new Attributes(['class' => 'test-with-options-decorator'])));
    }

    /**
     * @return string[]
     */
    public function getAttrs(): array
    {
        return $this->attrs;
    }

    /**
     * @param string[] $attrs
     *
     * @return $this
     */
    public function setAttrs(array $attrs): static
    {
        $this->attrs = $attrs;

        return $this;
    }

    protected function registerAttributeCallbacks(Attributes $attributes): void
    {
        $attributes
            ->registerAttributeCallback('attrs', null, $this->setAttrs(...));
    }
}
