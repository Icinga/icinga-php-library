<?php

namespace ipl\Web\Control\SearchBar;

use InvalidArgumentException;
use ipl\Stdlib\Data;
use ipl\Stdlib\Filter;
use LogicException;

class ValidatedOperator extends ValidatedTerm
{
    /**
     * Create a new ValidatedColumn from the given filter condition
     *
     * @param Filter\Condition $condition
     *
     * @return static
     *
     * @throws InvalidArgumentException In case the condition type is unknown
     */
    public static function fromFilterCondition(Filter\Condition $condition)
    {
        switch (true) {
            case $condition instanceof Filter\Unlike:
            case $condition instanceof Filter\Unequal:
                $operator = '!=';
                break;
            case $condition instanceof Filter\Like:
            case $condition instanceof Filter\Equal:
                $operator = '=';
                break;
            case $condition instanceof Filter\GreaterThan:
                $operator = '>';
                break;
            case $condition instanceof Filter\LessThan:
                $operator = '<';
                break;
            case $condition instanceof Filter\GreaterThanOrEqual:
                $operator = '>=';
                break;
            case $condition instanceof Filter\LessThanOrEqual:
                $operator = '<=';
                break;
            default:
                throw new InvalidArgumentException('Unknown condition type');
        }

        return new static($operator);
    }

    public function toTermData()
    {
        $termData = parent::toTermData();
        $termData['type'] = 'operator';

        return $termData;
    }

    public function toMetaData()
    {
        $data = new Data();

        if (! $this->isValid()) {
            $data->set('invalidOperatorMessage', $this->getMessage())
                ->set('invalidOperatorPattern', $this->getPattern());
        }

        return $data;
    }

    public function setSearchValue($searchValue)
    {
        throw new LogicException('Operators cannot be changed');
    }

    public function setLabel($label)
    {
        throw new LogicException('Operators cannot be changed');
    }
}
