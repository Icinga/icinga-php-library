<?php

namespace ipl\Orm;

use InvalidArgumentException;

class ColumnDefinition
{
    /** @var string The name of the column */
    protected string $name;

    /** @var ?string The label of the column */
    protected ?string $label = null;

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
    public function setLabel(?string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Create a new column definition based on the given options
     *
     * @param array $options
     *
     * @return static
     *
     * @throws InvalidArgumentException If the given options do not provide a name
     */
    public static function fromArray(array $options): static
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
