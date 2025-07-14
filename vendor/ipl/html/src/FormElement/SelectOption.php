<?php

namespace ipl\Html\FormElement;

use ipl\Html\BaseHtmlElement;

class SelectOption extends BaseHtmlElement
{
    protected $tag = 'option';

    /** @var string|int|null Value of the option */
    protected $value;

    /** @var string Label of the option */
    protected $label;

    /**
     * SelectOption constructor.
     *
     * @param string|int|null $value
     * @param string $label
     */
    public function __construct($value, string $label)
    {
        $this->value = $value === '' ? null : $value;
        $this->label = $label;

        $this->getAttributes()->registerAttributeCallback('value', [$this, 'getValueAttribute']);
    }

    /**
     * Set the label of the option
     *
     * @param string $label
     *
     * @return $this
     */
    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get the label of the option
     *
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * Get the value of the option
     *
     * @return string|int|null
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Callback for the value attribute
     *
     * @return mixed
     */
    public function getValueAttribute()
    {
        return (string) $this->getValue();
    }

    protected function assemble()
    {
        $this->setContent($this->getLabel());
    }
}
