<?php

namespace ipl\Web\Compat\FormDecorator;

use ipl\Html\Attribute;
use ipl\Html\Contract\DecorationResult;
use ipl\Html\Contract\Form;
use ipl\Html\Contract\FormDecoration;
use ipl\Html\Form as iplForm;

/**
 * Marks the form’s submit button as primary (adds the "btn-primary" CSS class).
 *
 * Note: This decorator only applies to {@see \ipl\Html\Form} instances because it relies on
 * the submit-button tracking provided by that implementation.
 */
class PrimaryButtonDecorator implements FormDecoration
{
    public function decorateForm(DecorationResult $result, Form $form): void
    {
        if ($form instanceof iplForm && $form->hasSubmitButton()) {
            $form->getSubmitButton()->getAttributes()
                ->addAttribute(new Attribute('class', 'btn-primary'));
        }
    }
}
