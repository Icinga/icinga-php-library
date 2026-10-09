<?php

namespace ipl\Html\FormDecoration;

use ipl\Html\Contract\DecorationResult;
use ipl\Html\Contract\FormElement;
use ipl\Html\Contract\FormElementDecoration;
use ipl\Html\Contract\HtmlElementInterface;
use ipl\Html\Contract\MutableHtml;
use ipl\Html\HtmlElement;
use ipl\Html\Text;

/**
 * Decorates the fieldset of the form element
 */
class FieldsetDecorator implements FormElementDecoration
{
    public function decorateFormElement(DecorationResult $result, FormElement $formElement): void
    {
        $isHtmlElement = $formElement instanceof HtmlElementInterface;
        if (! $formElement instanceof MutableHtml || ! $isHtmlElement || $formElement->getTag() !== 'fieldset') {
            return;
        }

        // No fallback id & aria-describedby required. The legend already provides an accessible label for the fieldset
        $description = $formElement->getDescription();
        if ($description !== null) {
            $formElement->prependHtml(new HtmlElement('p', content: new Text($description)));
        }

        $label = $formElement->getLabel();
        if ($label !== null) {
            $formElement->prependHtml(new HtmlElement('legend', null, Text::create($label)));
        }
    }
}
