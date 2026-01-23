<?php

namespace ipl\Tests\Html\FormDecorator;

use ipl\Html\Contract\DecorationResult;
use ipl\Html\Contract\FormElementDecoration;
use ipl\Html\Contract\FormElement;

/**
 * Render the $formElement
 */
class TestRenderElementDecorator implements FormElementDecoration
{
    public function getName(): string
    {
        return 'TestRenderElement';
    }

    public function decorateFormElement(DecorationResult $result, FormElement $formElement): void
    {
        $result->append($formElement);
    }
}
