<?php

namespace ipl\Stdlib\Filter;

abstract class Condition implements Rule, MetaDataProvider
{
    use MetaData;

    /** @var string */
    protected $column;

    /** @var mixed */
    protected $value;

    /**
     * Create a new Condition
     *
     * @param string $column
     * @param mixed $value
     */
    public function __construct($column, $value)
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
     * @param string $column
     *
     * @return $this
     */
    public function setColumn($column)
    {
        $this->column = $column;

        return $this;
    }

    /**
     * Get this condition's column
     *
     * @return string
     */
    public function getColumn()
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
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get this condition's value
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}
