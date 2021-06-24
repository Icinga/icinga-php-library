<?php

namespace ipl\Orm\Contract;

use ipl\Orm\Behavior;
use ipl\Stdlib\Filter;

interface RewriteFilterBehavior extends Behavior
{
    /**
     * Rewrite the given filter condition
     *
     * The condition can either be adjusted directly or replaced by an entirely new rule. The result must be
     * returned otherwise (NULL is returned) the original condition is kept as is.
     *
     * @param Filter\Condition $condition
     * @param string           $relation The absolute path (with a trailing dot) of the model
     *
     * @return Filter\Rule|null
     */
    public function rewriteCondition(Filter\Condition $condition, $relation = null);
}
