<?php

namespace ipl\Orm\Contract;

use ipl\Orm\ColumnDefinition;

interface RewriteColumnBehavior extends RewriteFilterBehavior
{
    /**
     * Rewrite the given column
     *
     * The result must be returned otherwise (NULL is returned) the original column is kept as is.
     *
     * @param mixed $column
     * @param ?string $relation The absolute path of the model. For reference only, don't include it in the result
     *
     * @return mixed
     */
    public function rewriteColumn($column, ?string $relation = null);

    /**
     * Get whether {@see rewriteColumn} might return an otherwise unknown column or expression
     *
     * @param string $name
     *
     * @return bool
     */
    public function isSelectableColumn(string $name): bool;

    /**
     * Rewrite the given column definition
     *
     * @param ColumnDefinition $def
     * @param string $relation The absolute path of the model. For reference only, don't include it in the result
     *
     * @return void
     */
    public function rewriteColumnDefinition(ColumnDefinition $def, string $relation): void;
}
