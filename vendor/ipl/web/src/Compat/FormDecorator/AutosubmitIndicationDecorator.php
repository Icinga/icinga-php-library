<?php

namespace ipl\Web\Compat\FormDecorator;

use ipl\Html\Attributes;
use ipl\Html\Contract\DecorationResult;
use ipl\Html\Contract\DecoratorOptions;
use ipl\Html\Contract\DecoratorOptionsInterface;
use ipl\Html\Contract\FormElementDecoration;
use ipl\Html\Contract\FormElement;
use ipl\Html\FormElement\CheckboxElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Web\Widget\Icon;

/**
 * Decorates autosubmit elements with an indicator to visualize auto-submission.
 */
class AutosubmitIndicationDecorator implements FormElementDecoration, DecoratorOptionsInterface
{
    use DecoratorOptions;
    use Translation;

    /** @var callable A callback used to generate a unique ID based on the element name */
    private $uniqueName = 'uniqid';

    public function decorateFormElement(DecorationResult $result, FormElement $formElement): void
    {
        if (
            ! $formElement instanceof CheckboxElement
            || ! in_array('autosubmit', (array) $formElement->getAttribute('class')->getValue(), true)
        ) {
            return;
        }

        if ($formElement->getAttributes()->has('id')) {
            $elementId = $formElement->getAttributes()->get('id')->getValue();
        } else {
            $elementId = call_user_func($this->uniqueName, $formElement->getName());
        }

        $autosubmitId = 'autosubmit_indicator_' . $elementId;
        $formElement->getAttributes()->add('aria-describedby', $autosubmitId);

        $title = $this->translate('This page will be automatically updated upon change of the value');

        $result
           ->append(new Icon(
               'rotate-right',
               Attributes::create(['aria-hidden' => 'true', 'role' => 'img', 'title' => $title, 'class' => 'spinner'])
           ))
           ->append(new HtmlElement(
               'span',
               Attributes::create(['class' => 'sr-only', 'id' => $autosubmitId]),
               new Text($title)
           ));
    }

    protected function registerAttributeCallbacks(Attributes $attributes): void
    {
        $attributes->registerAttributeCallback('uniqueName', null, fn($callback) => $this->uniqueName = $callback);
    }
}
