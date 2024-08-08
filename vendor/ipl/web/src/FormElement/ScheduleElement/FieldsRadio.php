<?php

namespace ipl\Web\FormElement\ScheduleElement;

use ipl\Html\Attributes;
use ipl\Html\FormElement\InputElement;
use ipl\Html\FormElement\RadioElement;
use ipl\Html\HtmlElement;
use ipl\Web\FormElement\ScheduleElement\Common\FieldsProtector;

class FieldsRadio extends RadioElement
{
    use FieldsProtector;

    protected function assemble()
    {
        $listItems = HtmlElement::create('ul', ['class' => ['schedule-element-fields', 'single-fields']]);
        foreach ($this->options as $option) {
            $radio = (new InputElement($this->getValueOfNameAttribute()))
                ->setValue($option->getValue())
                ->setType($this->type);

            $radio->setAttributes(clone $this->getAttributes());

            $htmlId = $this->protectId($option->getValue());
            $radio->getAttributes()
                ->set('id', $htmlId)
                ->registerAttributeCallback('checked', function () use ($option) {
                    return (string) $this->getValue() === (string) $option->getValue();
                })
                ->registerAttributeCallback('required', [$this, 'getRequiredAttribute'])
                ->registerAttributeCallback('disabled', function () use ($option) {
                    return $this->getAttributes()->get('disabled')->getValue() || $option->isDisabled();
                });

            $listItem = HtmlElement::create('li');
            $listItem->addHtml(
                $radio,
                HtmlElement::create('label', [
                    'for'      => $htmlId,
                    'class'    => $option->getLabelCssClass(),
                    'tabindex' => -1
                ], $option->getLabel())
            );
            $listItems->addHtml($listItem);
        }

        $this->addHtml($listItems);
    }

    protected function registerAttributeCallbacks(Attributes $attributes)
    {
        parent::registerAttributeCallbacks($attributes);

        $attributes
            ->registerAttributeCallback('protector', null, [$this, 'setIdProtector']);
    }
}
