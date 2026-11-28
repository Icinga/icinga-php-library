<?php

namespace ipl\Web\Control\SearchEditor;

use ipl\Html\Attributes;
use ipl\Html\Contract\DecorationResult;
use ipl\Html\Contract\FormElement;
use ipl\Html\Contract\FormElementDecoration;
use ipl\Html\Contract\MutableHtml;
use ipl\Html\HtmlElement;
use ipl\Html\Text;

class ConditionErrorsDecorator implements FormElementDecoration
{
    /**
     * Collects a condition fieldset's child element errors into one `.search-errors` list
     */
    public function decorateFormElement(DecorationResult $result, FormElement $formElement): void
    {
        if (! $formElement instanceof MutableHtml) {
            return;
        }

        $errors = new HtmlElement('ul', Attributes::create(['class' => 'search-errors']));

        foreach ($formElement->getContent() as $element) {
            if ($element instanceof FormElement) {
                foreach ($element->getMessages() as $message) {
                    $errors->addHtml(new HtmlElement('li', null, Text::create($message)));
                }
            }
        }

        if (! $errors->isEmpty()) {
            $formElement->addHtml($errors);
        }
    }
}
