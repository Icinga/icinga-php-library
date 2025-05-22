<?php

namespace ipl\Html\FormElement;

use ipl\Html\Attribute;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Contract\FormElement;
use ipl\Html\Contract\ValueCandidates;
use ipl\Html\Form;
use ipl\I18n\Translation;
use ipl\Stdlib\Messages;
use ipl\Validator\ValidatorChain;
use ReflectionProperty;

abstract class BaseFormElement extends BaseHtmlElement implements FormElement, ValueCandidates
{
    use Messages;
    use Translation;

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

    /** @var array<int, mixed> Value candidates of the element */
    protected $valueCandidates = [];

    /**
     * Create a new form element
     *
     * @param string $name       Name of the form element
     * @param mixed  $attributes Attributes of the form element
     */
    public function __construct($name, $attributes = null)
    {
        $this->setName($name);
        $this->init();

        if ($attributes !== null) {
            $this->addAttributes($attributes);
        }
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
     * Get the validators
     *
     * @return ValidatorChain
     */
    public function getValidators()
    {
        if ($this->validators === null) {
            $chain = new ValidatorChain();
            $this->addDefaultValidators($chain);
            $this->validators = $chain;
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

        return ! Form::isEmptyValue($value);
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

    public function getValueCandidates()
    {
        return $this->valueCandidates;
    }

    public function setValueCandidates(array $values)
    {
        $this->valueCandidates = $values;

        return $this;
    }

    public function onRegistered(Form $form)
    {
    }

    /**
     * Validate the element using all registered validators
     *
     * @return $this
     */
    public function validate()
    {
        $this->ensureAssembled();

        if (! $this->hasValue()) {
            if ($this->isRequired()) {
                $this->setMessages([$this->translate('This field is required.')]);
                $this->valid = false;
            } else {
                $this->valid = true;
            }
        } else {
            $this->valid = $this->getValidators()->isValid($this->getValue());
            $this->setMessages($this->getValidators()->getMessages());
        }

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

    /**
     * Initialize this form element
     *
     * If you want to initialize this element after construction, override this method
     */
    protected function init(): void
    {
    }

    /**
     * Add default validators
     */
    protected function addDefaultValidators(ValidatorChain $chain): void
    {
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

    /**
     * @deprecated
     *
     * {@see Attributes::get()} does not respect callbacks,
     * but we need the value of the callback to nest attribute names.
     */
    protected function getValueOfNameAttribute()
    {
        $attributes = $this->getAttributes();

        $callbacksProperty = new ReflectionProperty(get_class($attributes), 'callbacks');
        $callbacksProperty->setAccessible(true);
        $callbacks = $callbacksProperty->getValue($attributes);

        if (isset($callbacks['name'])) {
            $name = $callbacks['name']();

            if ($name instanceof Attribute) {
                return $name->getValue();
            }

            return $name;
        }

        return $this->getName();
    }
}
