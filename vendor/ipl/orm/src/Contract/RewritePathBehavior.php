<?php

namespace ipl\Orm\Contract;

use ipl\Orm\Behavior;

interface RewritePathBehavior extends Behavior
{
    /**
     * Rewrite the given relation path
     *
     * The result must be returned otherwise (NULL is returned) the original path is kept as is.
     *
     * @param string $path
     * @param ?string $relation The absolute path of the model. For reference only, don't include it in the result
     *
     * @return ?string
     */
    public function rewritePath(string $path, ?string $relation = null): ?string;
}
