<?php

namespace ipl\Html\FormDecorator;

use ipl\Html\Contract\FormElementDecorator;
use ipl\Html\FormElement\BaseFormElement;

/** @deprecated Use {@link FormElementDecorator} instead */
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
