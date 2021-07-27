<?php

namespace ipl\Html\Contract;

use ipl\Html\Attributes;

/**
 * Representation of form elements
 */
interface FormElement extends Wrappable
{
    /**
     * Get the attributes or options of the element
     *
     * @return Attributes
     */
    public function getAttributes();

    /**
     * Add attributes or options to the form element
     *
     * @param iterable $attributes
     *
     * @return $this
     */
    public function addAttributes($attributes);

    /**
     * Get the description for the element, if any
     *
     * @return string|null
     */
    public function getDescription();

    /**
     * Get the label for the element, if any
     *
     * @return string|null
     */
    public function getLabel();

    /**
     * Get the validation error messages
     *
     * @return array
     */
    public function getMessages();

    /**
     * Add a validation error message
     *
     * @param string $message
     *
     * @return $this
     */
    public function addMessage($message);

    /**
     * Get the name of the element
     *
     * @return string
     */
    public function getName();

    /**
     * Get whether the element has a value
     *
     * @return bool False if the element's value is null, the empty string or the empty array; true otherwise
     */
    public function hasValue();

    /**
     * Get the value of the element
     *
     * @return mixed
     */
    public function getValue();

    /**
     * Set the value of the element
     *
     * @param mixed $value
     *
     * @return $this
     */
    public function setValue($value);

    /**
     * Get whether the element has been validated
     *
     * @return bool
     */
    public function hasBeenValidated();

    /**
     * Get whether the element is ignored
     *
     * @return bool
     */
    public function isIgnored();

    /**
     * Get whether the element is required
     *
     * @return bool
     */
    public function isRequired();

    /**
     * Get whether the element is valid
     *
     * @return bool
     */
    public function isValid();
}
