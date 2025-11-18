<?php

namespace ipl\Web\Control\SearchBar;

use ipl\Stdlib\Data;
use ipl\Stdlib\Filter\Condition;

class ValidatedColumn extends ValidatedTerm
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
        return new static($condition->getColumn(), $condition->metaData()->get('columnLabel'));
    }

    public function toTermData()
    {
        $termData = parent::toTermData();
        $termData['type'] = 'column';

        return $termData;
    }

    public function toMetaData()
    {
        $data = new Data();
        if (($label = $this->getLabel()) !== null) {
            $data->set('columnLabel', $label);
        }

        if (! $this->isValid()) {
            $data->set('invalidColumnMessage', $this->getMessage())
                ->set('invalidColumnPattern', $this->getPattern());
        }

        return $data;
    }
}
