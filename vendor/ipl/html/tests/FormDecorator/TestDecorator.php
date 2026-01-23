<?php

namespace ipl\Tests\Html\FormDecorator;

use ipl\Html\Attributes;
use ipl\Html\Contract\DecorationResult;
use ipl\Html\Contract\FormElementDecoration;
use ipl\Html\Contract\FormElement;
use ipl\Html\HtmlElement;

/**
 * Wraps the $formElement with a div with class "test-decorator"
 */
class TestDecorator implements FormElementDecoration
{
    public function getName(): string
    {
        return 'Test';
    }

    public function decorateFormElement(DecorationResult $result, FormElement $formElement): void
    {
        $result->wrap(new HtmlElement('div', new Attributes(['class' => 'test-decorator'])));
    }
}
