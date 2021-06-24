<?php

namespace ipl\Stdlib;

use Closure;
use OutOfBoundsException;

/**
 * Trait for property access, mutation and array access.
 */
trait Properties
{
    /** @var array */
    protected $properties = [];

    /** @var array */
    protected $mutatedProperties = [];

    /** @var bool Whether accessors and mutators are enabled */
    protected $accessorsAndMutatorsEnabled = false;

    /**
     * Get whether a property with the given key exists
     *
     * @param string $key
     *
     * @return bool
     */
    public function hasProperty($key)
    {
        if (array_key_exists($key, $this->properties)) {
            return true;
        } elseif ($this->accessorsAndMutatorsEnabled) {
            $mutator = 'mutate' . Str::camel($key) . 'Property';

            if (method_exists($this, $mutator)) {
                return true;
            }
        }

        return false;
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
        if ($this->accessorsAndMutatorsEnabled) {
            $this->mutateProperty($key);
        }

        if (array_key_exists($key, $this->properties)) {
            if ($this->properties[$key] instanceof Closure) {
                $value = $this->properties[$key]($this);
                $this->setProperty($key, $value);
                return $value;
            }

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

        if ($this->accessorsAndMutatorsEnabled) {
            $this->mutateProperty($key);
        }

        return $this;
    }

    /**
     * Try to mutate the given key
     *
     * @param string $key
     * @todo Support for generators, if needed
     */
    protected function mutateProperty($key)
    {
        if (array_key_exists($key, $this->mutatedProperties)) {
            return;
        }

        $value = array_key_exists($key, $this->properties)
            ? $this->properties[$key]
            : null;
        $this->mutatedProperties[$key] = $value; // Prevents repeated checks

        $mutator = Str::camel('mutate_' . $key) . 'Property';
        if (method_exists($this, $mutator)) {
            $this->properties[$key] = $this->$mutator($value);
        }
    }

    /**
     * Check whether an offset exists
     *
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        if ($this->accessorsAndMutatorsEnabled) {
            $this->mutateProperty($offset);
        }

        return isset($this->properties[$offset]);
    }

    /**
     * Get the value for an offset
     *
     * @param mixed $offset
     *
     * @return mixed
     */
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
    public function offsetSet($offset, $value)
    {
        $this->setProperty($offset, $value);
    }

    /**
     * Unset the value for an offset
     *
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->properties[$offset]);
        unset($this->mutatedProperties[$offset]);
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
