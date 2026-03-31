<?php

namespace ipl\Html\FormDecorator;

use ipl\Html\Contract\FormElementDecoration;
use ipl\Html\FormElement\BaseFormElement;

/** @deprecated Use {@see FormElementDecoration} instead */
interface DecoratorInterface
{
    /**
     * Set the form element to decorate
     *
     * @param BaseFormElement $formElement
     *
     * @return static
     */
    public function decorate(BaseFormElement $formElement);
}
