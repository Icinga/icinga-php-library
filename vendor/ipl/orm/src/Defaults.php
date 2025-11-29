<?php

namespace ipl\Orm;

use IteratorAggregate;
use Traversable;

class Defaults implements IteratorAggregate
{
    /** @var array<string, mixed> Registered defaults */
    protected $defaults = [];

    /**
     * Iterate over the defaults
     *
     * @return Traversable
     */
    public function getIterator(): Traversable
    {
        foreach ($this->defaults as $column => $default) {
            yield $column => $default;
        }
    }

    /**
     * Add a default for the given property
     *
     * @param string $property
     * @param mixed $default If it's a closure, its interface is assumed to be
     *                       ({@see Model} $subject, string $propertyName)
     *
     * @return $this
     */
    public function add(string $property, $default): self
    {
        $this->defaults[$property] = $default;

        return $this;
    }

    /**
     * Get whether a default for the given property exists
     *
     * @param string $property
     *
     * @return bool
     */
    public function has(string $property): bool
    {
        return array_key_exists($property, $this->defaults);
    }
}
