<?php

namespace ipl\Html\FormElement;

use ipl\Html\Attribute;
use ipl\Html\Contract\FormSubmitElement;

class SubmitElement extends InputElement implements FormSubmitElement
{
    protected $type = 'submit';

    protected $buttonLabel;

    /**
     * Set the label of the element
     *
     * Unlike other elements, the label populates the element's `value` attribute
     * and is compared against the submitted value, so it must be a plain string.
     *
     * @param string $label
     *
     * @return $this
     */
    public function setLabel($label)
    {
        $this->buttonLabel = $label;

        return $this;
    }

    /**
     * @return string
     */
    public function getButtonLabel()
    {
        if ($this->buttonLabel === null) {
            return $this->getName();
        } else {
            return $this->buttonLabel;
        }
    }

    /**
     * @return mixed|static
     */
    public function getValueAttribute()
    {
        return new Attribute('value', $this->getButtonLabel());
    }

    public function hasBeenPressed()
    {
        return $this->getButtonLabel() === $this->getValue();
    }

    public function isIgnored()
    {
        return true;
    }
}
