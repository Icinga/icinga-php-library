<?php

namespace ipl\Html\Contract;

/**
 * Representation of form element decorator
 */
interface FormElementDecoration
{
    /**
     * Decorate the given form element
     *
     * A decorator can create HTML elements and apply attributes to the given $formElement element.
     * Only the elements added to {@see DecorationResult} are rendered in the end.
     *
     * The element can be added to the {@see DecorationResult} using the following three methods:
     * - {@see DecorationResult::append()} will add the given HTML to the end of the result
     * - {@see DecorationResult::prepend()} will prepend the given HTML to the beginning of the result
     * - {@see DecorationResult::wrap()} will set the given HTML as the container of the result
     *
     * **Reference implementation:**
     *
     *```
     *
     * public function decorateFormElement(DecorationResult $result, FormElement $formElement): void
     * {
     *     $description = $formElement->getDescription();
     *
     *     if ($description === null) {
     *         return;
     *     }
     *
     *     $result->append(new HtmlElement('p', null, new Text($description)));
     * }
     * ```
     *
     * @param DecorationResult $result
     * @param FormElement $formElement
     *
     * @return void
     */
    public function decorateFormElement(DecorationResult $result, FormElement $formElement): void;
}
