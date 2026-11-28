<?php

namespace ipl\Html\Contract;

use ipl\Html\Attributes;

/**
 * Options for decorators
 *
 * This trait is intended for use by the classes which implement {@see DecoratorOptionsInterface}.
 */
trait DecoratorOptions
{
    /** @var ?Attributes Attributes of the decorator */
    protected ?Attributes $attributes = null;

    /**
     * Get the attributes
     *
     * @return Attributes
     */
    public function getAttributes(): Attributes
    {
        if ($this->attributes === null) {
            $this->attributes = new Attributes();
            $this->registerAttributeCallbacks($this->attributes);
        }

        return $this->attributes;
    }

    /**
     * Register attribute callbacks
     *
     * Override this method in order to register attribute callbacks in concrete classes.
     *
     * @param Attributes $attributes
     */
    abstract protected function registerAttributeCallbacks(Attributes $attributes): void;
}
