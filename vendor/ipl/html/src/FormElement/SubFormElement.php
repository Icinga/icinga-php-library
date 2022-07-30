<?php

namespace ipl\Html\FormElement;

use ipl\Html\Attributes;

class SubFormElement extends BaseFormElement
{
    use FormElements;

    protected $tag = 'div';

    protected $defaultAttributes = [
        'class' => 'ipl-subform'
    ];

    public function getValue($name = null)
    {
        if ($name === null) {
            return $this->getValues();
        } else {
            return $this->getElement($name)->getValue();
        }
    }

    public function setValue($value)
    {
        $this->populate($value);

        return $this;
    }

    public function isValid()
    {
        foreach ($this->getElements() as $element) {
            if (! $element->isValid()) {
                return false;
            }
        }

        return true;
    }

    public function hasSubmitButton()
    {
        return true;
    }

    protected function registerValueCallback(Attributes $attributes)
    {
        $attributes->registerAttributeCallback(
            'value',
            null,
            [$this, 'setValue']
        );
    }
}
