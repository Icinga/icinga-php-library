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
     * returned otherwise (NULL is returned) processing continues normally. (Isn't restarted)
     *
     * If a result is returned, it is required to append the given absolute path of the model to the column.
     * Processing of the condition will be restarted, hence the column has to be an absolute path again.
     *
     * @param Filter\Condition $condition
     * @param string           $relation The absolute path (with a trailing dot) of the model
     *
     * @return Filter\Rule|null
     */
    public function rewriteCondition(Filter\Condition $condition, $relation = null);
}
