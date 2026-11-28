<?php

namespace ipl\Stdlib\Filter;

/**
 * Abstract filter condition matching a column against a value
 */
abstract class Condition implements Rule, MetaDataProvider
{
    use MetaData;

    /** @var string|string[] */
    protected string|array $column = [];

    protected mixed $value = null;

    /**
     * Create a new Condition
     *
     * @param string|string[] $column
     * @param mixed $value
     */
    public function __construct(string|array $column, mixed $value)
    {
        $this->setColumn($column)
            ->setValue($value);
    }

    /**
     * Clone this condition's meta data
     */
    public function __clone()
    {
        if ($this->metaData !== null) {
            $this->metaData = clone $this->metaData;
        }
    }

    /**
     * Set this condition's column
     *
     * @param string|string[] $column
     *
     * @return $this
     */
    public function setColumn(string|array $column): static
    {
        $this->column = $column;

        return $this;
    }

    /**
     * Get this condition's column
     *
     * @return string|string[]
     */
    public function getColumn(): string|array
    {
        return $this->column;
    }

    /**
     * Set this condition's value
     *
     * @param mixed $value
     *
     * @return $this
     */
    public function setValue(mixed $value): static
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get this condition's value
     *
     * @return mixed
     */
    public function getValue(): mixed
    {
        return $this->value;
    }
}
