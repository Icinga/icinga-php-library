<?php

namespace ipl\Html\FormElement;

use ipl\Html\Attribute;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Contract\FormElement;
use ipl\Stdlib\Messages;
use ipl\Validator\ValidatorChain;

abstract class BaseFormElement extends BaseHtmlElement implements FormElement
{
    use Messages;

    /** @var string Description of the element */
    protected $description;

    /** @var string Label of the element */
    protected $label;

    /** @var string Name of the element */
    protected $name;

    /** @var bool Whether the element is ignored */
    protected $ignored = false;

    /** @var bool Whether the element is required */
    protected $required = false;

    /** @var null|bool Whether the element is valid; null if the element has not been validated yet, bool otherwise */
    protected $valid;

    /** @var ValidatorChain Registered validators */
    protected $validators;

    /** @var mixed Value of the element */
    protected $value;

    /**
     * Create a new form element
     *
     * @param string $name       Name of the form element
     * @param mixed  $attributes Attributes of the form element
     */
    public function __construct($name, $attributes = null)
    {
        if ($attributes !== null) {
            $this->addAttributes($attributes);
        }
        $this->setName($name);
    }

    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set the description of the element
     *
     * @param string $description
     *
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set the label of the element
     *
     * @param string $label
     *
     * @return $this
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the name for the element
     *
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function isIgnored()
    {
        return $this->ignored;
    }

    /**
     * Set whether the element is ignored
     *
     * @param bool $ignored
     *
     * @return $this
     */
    public function setIgnored($ignored = true)
    {
        $this->ignored = (bool) $ignored;

        return $this;
    }

    public function isRequired()
    {
        return $this->required;
    }

    /**
     * Set whether the element is required
     *
     * @param bool $required
     *
     * @return $this
     */
    public function setRequired($required = true)
    {
        $this->required = (bool) $required;

        return $this;
    }

    public function isValid()
    {
        if ($this->valid === null) {
            $this->validate();
        }

        return $this->valid;
    }

    /**
     * Get whether the element has been validated and is not valid
     *
     * @return bool
     *
     * @deprecated Use {@link hasBeenValidated()} in combination with {@link isValid()} instead
     */
    public function hasBeenValidatedAndIsNotValid()
    {
        return $this->valid !== null && ! $this->valid;
    }

    /**
     * Get the validators
     *
     * @return ValidatorChain
     */
    public function getValidators()
    {
        if ($this->validators === null) {
            $this->validators = new ValidatorChain();
        }

        return $this->validators;
    }

    /**
     * Set the validators
     *
     * @param iterable $validators
     *
     * @return $this
     */
    public function setValidators($validators)
    {
        $this
            ->getValidators()
            ->clearValidators()
            ->addValidators($validators);

        return $this;
    }

    /**
     * Add validators
     *
     * @param iterable $validators
     *
     * @return $this
     */
    public function addValidators($validators)
    {
        $this->getValidators()->addValidators($validators);

        return $this;
    }

    public function hasValue()
    {
        $value = $this->getValue();

        return $value !== null && $value !== '' && $value !== [];
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value)
    {
        if ($value === '') {
            $this->value = null;
        } else {
            $this->value = $value;
        }
        $this->valid = null;

        return $this;
    }

    /**
     * Validate the element using all registered validators
     *
     * @return $this
     */
    public function validate()
    {
        $valid = true;

        foreach ($this->getValidators() as $validator) {
            if (! $validator->isValid($this->getValue())) {
                $valid = false;

                $this->addMessages($validator->getMessages());
            }
        }

        $this->valid = $valid;

        return $this;
    }

    public function hasBeenValidated()
    {
        return $this->valid !== null;
    }

    /**
     * Callback for the name attribute
     *
     * @return Attribute|string
     */
    public function getNameAttribute()
    {
        return $this->getName();
    }

    /**
     * Callback for the required attribute
     *
     * @return Attribute|null
     */
    public function getRequiredAttribute()
    {
        if ($this->isRequired()) {
            return new Attribute('required', true);
        }

        return null;
    }

    /**
     * Callback for the value attribute
     *
     * @return mixed
     */
    public function getValueAttribute()
    {
        return $this->getValue();
    }

    protected function registerValueCallback(Attributes $attributes)
    {
        $attributes->registerAttributeCallback(
            'value',
            [$this, 'getValueAttribute'],
            [$this, 'setValue']
        );
    }

    protected function registerAttributeCallbacks(Attributes $attributes)
    {
        $this->registerValueCallback($attributes);

        $attributes
            ->registerAttributeCallback('label', null, [$this, 'setLabel'])
            ->registerAttributeCallback('name', [$this, 'getNameAttribute'], [$this, 'setName'])
            ->registerAttributeCallback('description', null, [$this, 'setDescription'])
            ->registerAttributeCallback('validators', null, [$this, 'setValidators'])
            ->registerAttributeCallback('ignore', null, [$this, 'setIgnored'])
            ->registerAttributeCallback('required', [$this, 'getRequiredAttribute'], [$this, 'setRequired']);

        $this->registerCallbacks();
    }

    /** @deprecated Use {@link registerAttributeCallbacks()} instead */
    protected function registerCallbacks()
    {
    }
}
