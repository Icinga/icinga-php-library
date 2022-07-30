<?php

namespace ipl\Html\Contract;

use ipl\Html\ValidHtml;

/**
 * Representation of form element decorators
 */
interface FormElementDecorator extends ValidHtml
{
    /**
     * Decorate the given form element
     *
     * @param FormElement $formElement
     */
    public function decorate(FormElement $formElement);
}
