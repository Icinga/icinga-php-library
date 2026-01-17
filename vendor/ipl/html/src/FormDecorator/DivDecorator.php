<?php

namespace ipl\Html\FormDecorator;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Contract\FormElement;
use ipl\Html\Contract\FormElementDecoration;
use ipl\Html\Contract\FormElementDecorator;
use ipl\Html\Contract\FormSubmitElement;
use ipl\Html\FormElement\FieldsetElement;
use ipl\Html\FormElement\HiddenElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Html\Text;

/**
 * Form element decorator based on div elements
 *
 * @deprecated Use one of the new {@see FormElementDecoration} decorators instead
 */
class DivDecorator extends BaseHtmlElement implements FormElementDecorator
{
    /** @var string CSS class to use for submit elements */
    public const SUBMIT_ELEMENT_CLASS = 'form-control';

    /** @var string CSS class to use for all input elements */
    public const INPUT_ELEMENT_CLASS = 'form-element';

    /** @var string CSS class to use for form descriptions */
    public const DESCRIPTION_CLASS = 'form-element-description';

    /** @var string CSS class to use for form errors */
    public const ERROR_CLASS = 'form-element-errors';

    /** @var string CSS class to set on the decorator if the element has errors */
    public const ERROR_HINT_CLASS = 'has-error';

    /** @var FormElement The decorated form element */
    protected $formElement;

    protected $tag = 'div';

    public function decorate(FormElement $formElement)
    {
        if ($formElement instanceof HiddenElement) {
            return;
        }

        $decorator = clone $this;

        /**
         * Wrapper logic can be overridden to propagate the decorator.
         * So here we make sure that a yet unbound decorator is passed.
         *
         * {@see FieldsetElement::setWrapper()}
         */
        $formElement->prependWrapper($decorator);

        $decorator->formElement = $formElement;

        $classes = [static::INPUT_ELEMENT_CLASS];
        if ($formElement instanceof FormSubmitElement) {
            $classes[] = static::SUBMIT_ELEMENT_CLASS;
        }

        $decorator->getAttributes()->add('class', $classes);
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

        return $this->formElement->ensureAssembled();
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
            if ($this->formElement instanceof FieldsetElement) {
                return new HtmlElement('legend', null, Text::create($label));
            } else {
                $attributes = null;
                if ($this->formElement->getAttributes()->has('id')) {
                    $attributes = new Attributes(['for' => $this->formElement->getAttributes()->get('id')->getValue()]);
                }

                return Html::tag('label', $attributes, $label);
            }
        }

        return null;
    }

    protected function assemble()
    {
        if ($this->formElement->hasBeenValidated() && ! $this->formElement->isValid()) {
            $this->getAttributes()->add('class', static::ERROR_HINT_CLASS);
        }

        if ($this->formElement instanceof FieldsetElement) {
            $element = $this->assembleElement();
            $element->prependHtml(...Html::wantHtmlList([
                $this->assembleLabel(),
                $this->assembleDescription()
            ]));

            $this->addHtml(...Html::wantHtmlList([
                $element,
                $this->assembleErrors()
            ]));
        } else {
            $this->addHtml(...Html::wantHtmlList([
                $this->assembleLabel(),
                $this->assembleElement(),
                $this->assembleDescription(),
                $this->assembleErrors()
            ]));
        }
    }
}
