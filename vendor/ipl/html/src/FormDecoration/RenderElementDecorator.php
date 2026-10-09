<?php

namespace ipl\Html\FormDecoration;

use ipl\Html\Contract\DecorationResult;
use ipl\Html\Contract\FormElement;
use ipl\Html\Contract\FormElementDecoration;

/**
 * Render the form element itself
 */
class RenderElementDecorator implements FormElementDecoration
{
    public function decorateFormElement(DecorationResult $result, FormElement $formElement): void
    {
        $result->append($formElement);
    }
}
