<?php

namespace ipl\Html\Contract;

use Evenement\EventEmitterInterface;
use InvalidArgumentException;

interface FormElements extends EventEmitterInterface
{
    /** @var string Event emitted when an element is registered */
    public const ON_ELEMENT_REGISTERED = 'elementRegistered';

    /**
     * Get all elements
     *
     * @return FormElement[]
     */
    public function getElements();

    /**
     * Get whether the given element exists
     *
     * @param string|FormElement $element
     *
     * @return bool
     */
    public function hasElement(string|FormElement $element);

    /**
     * Get the element by the given name
     *
     * @param string $name
     *
     * @return FormElement
     *
     * @throws InvalidArgumentException If no element with the given name exists
     */
    public function getElement(string $name);

    /**
     * Add an element
     *
     * @param string|FormElement $typeOrElement Type of the element as string or an instance of FormElement
     * @param null|string $name Name of the element
     * @param null|array<string, mixed> $options Element options as key-value pairs
     *
     * @return $this
     *
     * @throws InvalidArgumentException If the element is invalid (e.g., wrong type, no name)
     */
    public function addElement(string|FormElement $typeOrElement, ?string $name = null, ?array $options = null);

    /**
     * Register an element
     *
     * Registers the element for value and validation handling but does not add it to the render stack.
     * Emits event {@see self::ON_ELEMENT_REGISTERED} with the element as parameter.
     *
     * @param FormElement $element
     *
     * @return $this
     *
     * @throws InvalidArgumentException If $element does not provide a name
     */
    public function registerElement(FormElement $element);

    /**
     * Remove an element
     *
     * @param string|FormElement $element
     *
     * @return $this
     */
    public function removeElement(string|FormElement $element);

    /**
     * Get the value of the element specified by name
     *
     * Returns $default if the element does not exist or has no value.
     *
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    public function getValue(string $name, mixed $default = null);

    /**
     * Get the values for all but ignored elements
     *
     * @return array<string, mixed> Values as name-value pairs
     */
    public function getValues();

    /**
     * Populate values of registered elements
     *
     * @param iterable<string, mixed> $values Values as name-value pairs
     *
     * @return $this
     */
    public function populate(iterable $values);
}
