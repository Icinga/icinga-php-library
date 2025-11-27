<?php

namespace ipl\Html\FormDecoration;

use ipl\Html\Attributes;
use ipl\Html\Contract\DecorationResult;
use ipl\Html\Contract\DecoratorOptions;
use ipl\Html\Contract\DecoratorOptionsInterface;
use ipl\Html\Contract\FormElement;
use ipl\Html\Contract\FormElementDecoration;
use ipl\Html\Contract\HtmlElementInterface;
use ipl\Html\FormElement\RadioElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Html\ValidHtml;

/**
 * Decorates the description of the form element
 */
class DescriptionDecorator implements FormElementDecoration, DecoratorOptionsInterface
{
    use DecoratorOptions;

    /** @var string|string[] CSS classes to apply */
    protected string|array $class = 'form-element-description';

    /** @var callable A callback used to generate a unique ID based on the element name */
    private $uniqueName = 'uniqid';

    /**
     * Get the css class(es)
     *
     * @return string|string[]
     */
    public function getClass(): string|array
    {
        return $this->class;
    }

    /**
     * Set the css class(es)
     *
     * @param string|string[] $class
     *
     * @return $this
     */
    public function setClass(string|array $class): static
    {
        $this->class = $class;

        return $this;
    }

    public function decorateFormElement(DecorationResult $result, FormElement $formElement): void
    {
        $isHtmlElement = $formElement instanceof HtmlElementInterface;

        if ($formElement->getDescription() === null || ($isHtmlElement && $formElement->getTag() === 'fieldset')) {
            return;
        }

        $elementDescription = $this->getElementDescription($formElement);
        if ($isHtmlElement) {
            if ($formElement->getAttributes()->has('id')) {
                $elementId = $formElement->getAttributes()->get('id')->getValue();
            } else {
                $elementId = call_user_func($this->uniqueName, $formElement->getName());

                // RadioElement applies all its attributes to each of its options, so we cannot set a fallback
                // id attribute here.
                if (! $formElement instanceof RadioElement) {
                    $formElement->getAttributes()->set('id', $elementId);
                }
            }

            $descriptionId = 'desc_' . $elementId;
            $formElement->getAttributes()->set('aria-describedby', $descriptionId);

            $elementDescription->getAttributes()->set('id', $descriptionId);
        }

        $elementDescription->getAttributes()->add('class', $this->getClass());

        $result->append($elementDescription);
    }

    /**
     * Get the element description as HTML
     *
     * @param FormElement $formElement
     *
     * @return HtmlElementInterface & ValidHtml
     */
    protected function getElementDescription(FormElement $formElement): HtmlElementInterface & ValidHtml
    {
        return new HtmlElement('p', content: new Text($formElement->getDescription()));
    }

    protected function registerAttributeCallbacks(Attributes $attributes): void
    {
        $attributes->registerAttributeCallback('class', null, $this->setClass(...));
        $attributes->registerAttributeCallback(
            'uniqueName',
            null,
            function ($callback) {
                $this->uniqueName = $callback;
            }
        );
    }
}
