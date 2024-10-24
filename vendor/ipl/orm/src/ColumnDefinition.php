<?php

namespace ipl\Orm;

use InvalidArgumentException;
use LogicException;

class ColumnDefinition
{
    /** @var string The name of the column */
    protected $name;

    /** @var ?string The label of the column */
    protected $label;

    /**
     * Create a new column definition
     *
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * Get the column name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the column label
     *
     * @return ?string
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * Set the column label
     *
     * @param ?string $label
     *
     * @return $this
     */
    public function setLabel(?string $label): self
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Create a new column definition based on the given options
     *
     * @param array $options
     *
     * @return self
     */
    public static function fromArray(array $options): self
    {
        if (! isset($options['name'])) {
            throw new InvalidArgumentException('$options must provide a name');
        }

        $self = new static($options['name']);
        if (isset($options['label'])) {
            $self->setLabel($options['label']);
        }

        return $self;
    }
}
