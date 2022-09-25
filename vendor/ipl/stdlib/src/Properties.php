<?php

namespace ipl\Stdlib;

use OutOfBoundsException;
use Traversable;

/**
 * Trait for property access, mutation and array access.
 */
trait Properties
{
    /** @var array */
    private $properties = [];

    /**
     * Get whether this class has any properties
     *
     * @return bool
     */
    public function hasProperties()
    {
        return ! empty($this->properties);
    }

    /**
     * Get whether a property with the given key exists
     *
     * @param string $key
     *
     * @return bool
     */
    public function hasProperty($key)
    {
        return array_key_exists($key, $this->properties);
    }

    /**
     * Set the given properties
     *
     * @param array $properties
     *
     * @return $this
     */
    public function setProperties(array $properties)
    {
        foreach ($properties as $key => $value) {
            $this->setProperty($key, $value);
        }

        return $this;
    }

    /**
     * Get the property by the given key
     *
     * @param string $key
     *
     * @return mixed
     *
     * @throws OutOfBoundsException If the property by the given key does not exist
     */
    protected function getProperty($key)
    {
        if (array_key_exists($key, $this->properties)) {
            return $this->properties[$key];
        }

        throw new OutOfBoundsException("Can't access property '$key'. Property does not exist");
    }

    /**
     * Set a property with the given key and value
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    protected function setProperty($key, $value)
    {
        $this->properties[$key] = $value;

        return $this;
    }

    /**
     * Iterate over all existing properties
     *
     * @return Traversable
     */
    public function getIterator(): Traversable
    {
        foreach ($this->properties as $key => $value) {
            yield $key => $value;
        }
    }

    /**
     * Check whether an offset exists
     *
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return isset($this->properties[$offset]);
    }

    /**
     * Get the value for an offset
     *
     * @param mixed $offset
     *
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->getProperty($offset);
    }

    /**
     * Set the value for an offset
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value): void
    {
        $this->setProperty($offset, $value);
    }

    /**
     * Unset the value for an offset
     *
     * @param mixed $offset
     */
    public function offsetUnset($offset): void
    {
        unset($this->properties[$offset]);
    }

    /**
     * Get the value of a non-public property
     *
     * This is a PHP magic method which is implicitly called upon access to non-public properties,
     * e.g. `$value = $object->property;`.
     * Do not call this method directly.
     *
     * @param mixed $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        return $this->getProperty($key);
    }

    /**
     * Set the value of a non-public property
     *
     * This is a PHP magic method which is implicitly called upon access to non-public properties,
     * e.g. `$object->property = $value;`.
     * Do not call this method directly.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function __set($key, $value)
    {
        $this->setProperty($key, $value);
    }

    /**
     * Check whether a non-public property is defined and not null
     *
     * This is a PHP magic method which is implicitly called upon access to non-public properties,
     * e.g. `isset($object->property);`.
     * Do not call this method directly.
     *
     * @param string $key
     *
     * @return bool
     */
    public function __isset($key)
    {
        return $this->offsetExists($key);
    }

    /**
     * Unset the value of a non-public property
     *
     * This is a PHP magic method which is implicitly called upon access to non-public properties,
     * e.g. `unset($object->property);`. This method does nothing if the property does not exist.
     * Do not call this method directly.
     *
     * @param string $key
     */
    public function __unset($key)
    {
        $this->offsetUnset($key);
    }
}
