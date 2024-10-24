<?php

namespace ipl\Html\FormElement;

use ipl\Html\Attribute;
use ipl\Html\Attributes;

class InputElement extends BaseFormElement
{
    /** @var string Type of the input */
    protected $type;

    protected $tag = 'input';

    /**
     * Get the type of the input
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set the type of the input
     *
     * @param string $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = (string) $type;

        return $this;
    }

    protected function registerAttributeCallbacks(Attributes $attributes)
    {
        parent::registerAttributeCallbacks($attributes);

        $attributes->registerAttributeCallback(
            'type',
            [$this, 'getType'],
            [$this, 'setType']
        );
    }
}
