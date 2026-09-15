<?php

namespace ipl\Web\Compat\FormDecorator;

use ipl\Html\Attribute;
use ipl\Html\Contract\DecorationResult;
use ipl\Html\Contract\Form;
use ipl\Html\Contract\FormDecoration;
use ipl\Html\Contract\FormSubmitElement;

class PrimaryButtonDecorator implements FormDecoration
{
    public function decorateForm(DecorationResult $result, Form $form): void
    {
        foreach ($form->getElements() as $element) {
            if ($element instanceof FormSubmitElement) {
                $element->getAttributes()->addAttribute(new Attribute('class', 'btn-primary'));
            }
        }
    }
}
