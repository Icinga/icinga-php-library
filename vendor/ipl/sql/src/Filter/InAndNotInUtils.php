<?php

namespace ipl\Sql\Filter;

use ipl\Sql\Select;

trait InAndNotInUtils
{
    /** @var string[]|string */
    protected $column;

    /** @var Select */
    protected $value;

    /**
     * Get the columns of this condition
     *
     * @return string[]|string
     */
    public function getColumn()
    {
        return $this->column;
    }

    /**
     * Set the columns of this condition
     *
     * @param string[]|string $column
     *
     * @return $this
     */
    public function setColumn($column): self
    {
        $this->column = $column;

        return $this;
    }

    /**
     * Get the value of this condition
     *
     * @return Select
     */
    public function getValue(): Select
    {
        return $this->value;
    }

    /**
     * Set the value of this condition
     *
     * @param Select $value
     *
     * @return $this
     */
    public function setValue($value): self
    {
        $this->value = $value;

        return $this;
    }
}
