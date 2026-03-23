<?php

namespace ipl\Html\FormElement;

use ipl\Html\Attributes;

class RadioOption
{
    /** @var string The default label class */
    public const LABEL_CLASS = 'radio-label';

    /** @var string|int|null Value of the option */
    protected $value;

    /** @var string Label of the option */
    protected $label;

    /** @var mixed Css class of the option's label */
    protected $labelCssClass = self::LABEL_CLASS;

    /** @var bool Whether the radio option is disabled */
    protected $disabled = false;

    /** @var Attributes */
    protected $attributes;

    /**
     * RadioOption constructor.
     *
     * @param string|int|null $value
     * @param string $label
     */
    public function __construct($value, string $label)
    {
        $this->value = $value === '' ? null : $value;
        $this->label = $label;
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
     * Set css class to the option label
     *
     * @param string|string[] $labelCssClass
     *
     * @return $this
     */
    public function setLabelCssClass($labelCssClass): self
    {
        $this->labelCssClass = $labelCssClass;

        return $this;
    }

    /**
     * Get css class of the option label
     *
     * @return string|string[]
     */
    public function getLabelCssClass()
    {
        return $this->labelCssClass;
    }

    /**
     * Set whether to disable the option
     *
     * @param bool $disabled
     *
     * @return $this
     */
    public function setDisabled(bool $disabled = true): self
    {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * Get whether the option is disabled
     *
     * @return bool
     */
    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    /**
     * Add the attributes
     *
     * @param Attributes $attributes
     *
     * @return $this
     */
    public function addAttributes(Attributes $attributes): self
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * Get the attributes
     *
     * @return Attributes
     */
    public function getAttributes(): Attributes
    {
        if ($this->attributes === null) {
            $this->attributes = new Attributes();
        }

        return $this->attributes;
    }
}
