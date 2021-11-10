<?php

namespace ipl\Web\Control\SearchBar;

use ipl\Stdlib\Data;
use ipl\Stdlib\Filter\Condition;

class ValidatedValue extends ValidatedTerm
{
    /**
     * Create a new ValidatedColumn from the given filter condition
     *
     * @param Condition $condition
     *
     * @return static
     */
    public static function fromFilterCondition(Condition $condition)
    {
        return new static($condition->getValue());
    }

    public function toMetaData()
    {
        $data = new Data();

        if (! $this->isValid()) {
            $data->set('invalidValueMessage', $this->getMessage())
                ->set('invalidValuePattern', $this->getPattern());
        }

        return $data;
    }
}
