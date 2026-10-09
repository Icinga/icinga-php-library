<?php

namespace ipl\Html\Contract;

use InvalidArgumentException;
use ipl\Html\Attribute;
use ipl\Html\Attributes;

/**
 * Representation of a html element
 *
 * @phpstan-import-type AttributeValue from Attribute
 * @phpstan-import-type AttributesType from Attributes
 */
interface HtmlElementInterface
{
    /**
     * Get the HTML tag of the element
     *
     * @return non-empty-string
     */
    public function getTag();

    /**
     * Get the attributes of the element
     *
     * @return Attributes
     */
    public function getAttributes();

    /**
     * Add the given attributes to the element
     *
     * @param Attributes|AttributesType $attributes
     *
     * @return $this
     */
    public function addAttributes($attributes);

    /**
     * Return true if the attribute with the given name exists, false otherwise
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasAttribute(string $name): bool;

    /**
     * Get the attribute with the given name
     *
     * If the attribute does not already exist, an empty one is automatically created and added to the attributes.
     *
     * @param string $name
     *
     * @return Attribute
     *
     * @throws InvalidArgumentException If the attribute does not yet exist and its name contains special characters
     */
    public function getAttribute(string $name): Attribute;

    /**
     * Set the attribute with the given name and value
     *
     * If the attribute with the given name already exists, it gets overridden.
     *
     * @param string            $name  The name of the attribute
     * @param AttributeValue    $value The value of the attribute
     *
     * @return $this
     */
    public function setAttribute($name, $value);

    /**
     * Remove the attribute with the given name or remove the given value from the attribute
     *
     * @param string            $name  The name of the attribute
     * @param AttributeValue    $value The value to remove if specified
     *
     * @return ?Attribute The removed or changed attribute, if any, otherwise null
     */
    public function removeAttribute(string $name, $value = null): ?Attribute;

    /**
     * Get whether the element is void
     *
     * @return bool
     */
    public function isVoid();
}
