<?php

namespace ipl\Html\FormElement;

class TextareaElement extends BaseFormElement
{
    protected $tag = 'textarea';

    public function setValue($value)
    {
        parent::setValue($value);

        // A textarea's content actually is the value
        $this->setContent($value);

        return $this;
    }

    public function getValueAttribute()
    {
        // textarea elements don't have a value attribute
        return null;
    }
}
