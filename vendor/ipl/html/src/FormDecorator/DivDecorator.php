<?php

namespace ipl\Html\FormDecorator;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Contract\FormElement;
use ipl\Html\Contract\FormElementDecorator;
use ipl\Html\Contract\FormSubmitElement;
use ipl\Html\FormElement\HiddenElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Html\Text;

/**
 * Form element decorator based on div elements
 */
class DivDecorator extends BaseHtmlElement implements FormElementDecorator
{
    /** @var string CSS class to use for submit elements */
    const SUBMIT_ELEMENT_CLASS = 'form-control';

    /** @var string CSS class to use for all input elements */
    const INPUT_ELEMENT_CLASS = 'form-element';

    /** @var string CSS class to use for form descriptions */
    const DESCRIPTION_CLASS = 'form-element-description';

    /** @var string CSS class to use for form errors */
    const ERROR_CLASS = 'form-element-errors';

    /** @var string CSS class to set on the decorator if the element has errors */
    const ERROR_HINT_CLASS = 'has-error';

    /** @var FormElement The decorated form element */
    protected $formElement;

    protected $tag = 'div';

    public function decorate(FormElement $formElement)
    {
        if ($formElement instanceof HiddenElement) {
            return;
        }

        $decorator = clone $this;

        $decorator->formElement = $formElement;

        $classes = [static::INPUT_ELEMENT_CLASS];
        if ($formElement instanceof FormSubmitElement) {
            $classes[] = static::SUBMIT_ELEMENT_CLASS;
        }

        $decorator->getAttributes()->add('class', $classes);

        $formElement->prependWrapper($decorator);
    }

    protected function assembleDescription()
    {
        $description = $this->formElement->getDescription();

        if ($description !== null) {
            $descriptionId = null;
            if ($this->formElement->getAttributes()->has('id')) {
                $descriptionId = 'desc_' . $this->formElement->getAttributes()->get('id')->getValue();
                $this->formElement->getAttributes()->set('aria-describedby', $descriptionId);
            }

            return Html::tag('p', ['id' => $descriptionId, 'class' => static::DESCRIPTION_CLASS], $description);
        }

        return null;
    }

    protected function assembleElement()
    {
        if ($this->formElement->isRequired()) {
            $this->formElement->getAttributes()->set('aria-required', 'true');
        }

        return $this->formElement;
    }

    protected function assembleErrors()
    {
        $errors = new HtmlElement('ul', Attributes::create(['class' => static::ERROR_CLASS]));

        foreach ($this->formElement->getMessages() as $message) {
            $errors->addHtml(
                new HtmlElement('li', Attributes::create(['class' => static::ERROR_CLASS]), Text::create($message))
            );
        }

        if (! $errors->isEmpty()) {
            return $errors;
        }

        return null;
    }

    protected function assembleLabel()
    {
        $label = $this->formElement->getLabel();

        if ($label !== null) {
            $attributes = null;
            if ($this->formElement->getAttributes()->has('id')) {
                $attributes = new Attributes(['for' => $this->formElement->getAttributes()->get('id')->getValue()]);
            }

            return Html::tag('label', $attributes, $label);
        }

        return null;
    }

    protected function assemble()
    {
        if ($this->formElement->hasBeenValidated() && ! $this->formElement->isValid()) {
            $this->getAttributes()->add('class', static::ERROR_HINT_CLASS);
        }

        $this->addHtml(...Html::wantHtmlList([
            $this->assembleLabel(),
            $this->assembleElement(),
            $this->assembleDescription(),
            $this->assembleErrors()
        ]));
    }
}
