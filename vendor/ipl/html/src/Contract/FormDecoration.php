<?php

namespace ipl\Html\Contract;

/**
 * Representation of a form decorator
 */
interface FormDecoration
{
    /**
     * Decorate the given form
     *
     * A decorator can create HTML elements and apply attributes to the given form.
     * Only the elements added to {@see DecorationResult} are rendered in the end.
     *
     * The element can be added to the {@see DecorationResult} using the following three methods:
     * - {@see DecorationResult::append()} will add the element to the end of the result.
     * - {@see DecorationResult::prepend()} will add the element to the beginning of the result.
     * - {@see DecorationResult::wrap()} will wrap the result with the given element.
     *
     * **Reference implementation:**
     *
     *```
     * public function decorateForm(DecorationResult $result, Form $form): void
     * {
     *     if (! $form->hasChanges()) {
     *         return;
     *     }
     *
     *     $result->prepend(new HtmlElement('p', null, Text::create('You have unsaved changes.')));
     * }
     * ```
     *
     * @param DecorationResult $result
     * @param Form $form
     *
     * @return void
     */
    public function decorateForm(DecorationResult $result, Form $form): void;
}
