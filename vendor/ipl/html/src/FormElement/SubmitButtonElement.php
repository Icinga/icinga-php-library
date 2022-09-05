<?php

namespace ipl\Html\FormElement;

use ipl\Html\Contract\FormSubmitElement;

class SubmitButtonElement extends ButtonElement implements FormSubmitElement
{
    protected $defaultAttributes = ['type' => 'submit'];

    protected $value = 'y';

    public function setLabel($label)
    {
        return $this->setContent($label);
    }

    public function hasBeenPressed()
    {
        return (bool) $this->getValue();
    }

    public function isIgnored()
    {
        return true;
    }
}
