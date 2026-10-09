<?php

namespace ipl\Orm\Contract;

use ipl\Orm\Behavior;
use ipl\Orm\Query;

interface QueryAwareBehavior extends Behavior
{
    /**
     * Set the query
     *
     * @param Query $query
     *
     * @return $this
     */
    public function setQuery(Query $query);
}
