<?php

namespace ipl\Html\Contract;

use ipl\Html\ValidHtml;

/**
 * Representation of form element decorators
 *
 * @deprecated Use {@see FormElementDecoration} instead
 */
interface FormElementDecorator extends ValidHtml
{
    /**
     * Decorate the given form element
     *
     * Decoration works by calling `prependWrapper()` on the form element,
     * passing a clone of the decorator. Hidden elements are to be ignored.
     *
     * **Reference implementation:**
     *
     * ```php
     * public function decorate(FormElement $formElement)
     * {
     *     if ($formElement instanceof HiddenElement) {
     *         return;
     *     }
     *
     *     $decorator = clone $this;
     *
     *     // Wrapper logic can be overridden to adjust or propagate the decorator.
     *     // So here we make sure that a yet unbound decorator is passed.
     *     $formElement->prependWrapper($decorator);
     *
     *     ...
     * }
     * ```
     *
     * @param FormElement $formElement
     */
    public function decorate(FormElement $formElement);
}
