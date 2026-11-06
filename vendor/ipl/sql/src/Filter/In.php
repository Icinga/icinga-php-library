<?php

namespace ipl\Sql\Filter;

use ipl\Sql\Select;
use ipl\Stdlib\Filter;

class In extends Filter\Condition
{
    /**
     * Create a new sql IN condition
     *
     * @param string|string[] $column
     * @param Select $select
     */
    public function __construct(string|array $column, Select $select)
    {
        parent::__construct($column, $select);
    }
}
