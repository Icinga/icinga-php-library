<?php

namespace ipl\Sql\Filter;

use ipl\Sql\Select;
use ipl\Stdlib\Filter;

class In extends Filter\Condition
{
    use InAndNotInUtils;

    /**
     * Create a new sql IN condition
     *
     * @param string[]|string $column
     * @param Select $select
     */
    public function __construct($column, Select $select)
    {
        $this
            ->setColumn($column)
            ->setValue($select);
    }
}
