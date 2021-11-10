<?php

namespace ipl\Orm\Contract;

interface RewriteBehavior extends RewriteFilterBehavior
{
    /**
     * Rewrite the given relation path
     *
     * The result must be returned otherwise (NULL is returned) the original path is kept as is.
     *
     * @param string $path
     * @param string $relation The absolute path of the model. For reference only, don't include it in the result
     *
     * @return string|null
     */
    public function rewritePath($path, $relation = null);

    /**
     * Rewrite the given column
     *
     * The result must be returned otherwise (NULL is returned) the original column is kept as is.
     *
     * @param string $column
     * @param string $relation The absolute path of the model. For reference only, don't include it in the result
     *
     * @return string|null
     */
    public function rewriteColumn($column, $relation = null);
}
