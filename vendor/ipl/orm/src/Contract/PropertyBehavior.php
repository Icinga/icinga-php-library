<?php

namespace ipl\Orm\Contract;

use ipl\Orm\Model;
use OutOfBoundsException;

abstract class PropertyBehavior implements RetrieveBehavior, PersistBehavior
{
    /** @var array Property names of which the value should be processed */
    protected $properties;

    /**
     * PropertyBehavior constructor
     *
     * @param array $properties Property names to process, as values
     */
    public function __construct(array $properties)
    {
        if (is_int(key($properties))) {
            $this->properties = array_flip($properties);
        } else {
            $this->properties = $properties;
        }
    }

    public function retrieve(Model $model)
    {
        foreach ($this->properties as $key => $ctx) {
            try {
                $model[$key] = $this->fromDb($model[$key], $key, $ctx);
            } catch (OutOfBoundsException $_) {
                // pass
            }
        }
    }

    public function persist(Model $model)
    {
        foreach ($this->properties as $key => $ctx) {
            try {
                $model[$key] = $this->toDb($model[$key], $key, $ctx);
            } catch (OutOfBoundsException $_) {
                // pass
            }
        }
    }

    /**
     * Transform the given value, just fetched from the database
     *
     * @param mixed  $value
     * @param string $key
     *
     * @return mixed
     */
    public function retrieveProperty($value, $key)
    {
        if (! isset($this->properties[$key])) {
            return $value;
        }

        return $this->fromDb($value, $key, $this->properties[$key]);
    }

    /**
     * Transform the given value, about to be persisted to the database
     *
     * @param mixed  $value
     * @param string $key
     *
     * @return mixed
     */
    public function persistProperty($value, $key)
    {
        if (! isset($this->properties[$key])) {
            return $value;
        }

        return $this->toDb($value, $key, $this->properties[$key]);
    }

    /**
     * Transform the given value which has just been fetched from the database
     *
     * @param mixed  $value
     * @param string $key
     * @param mixed $context
     *
     * @return mixed
     */
    abstract public function fromDb($value, $key, $context);

    /**
     * Transform the given value which is about to be persisted to the database
     *
     * @param mixed  $value
     * @param string $key
     * @param mixed $context
     *
     * @return mixed
     */
    abstract public function toDb($value, $key, $context);
}
