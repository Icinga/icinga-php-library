<?php

namespace ipl\Orm\Common;

use ipl\Stdlib\Str;

class SortUtil
{
    /**
     * Create the sort column(s) and direction(s) from the given sort spec
     *
     * @param array|string $sort
     *
     * @return array|null Sort column(s) and direction(s) suitable for {@link OrderByInterface::orderBy()}
     */
    public static function createOrderBy($sort)
    {
        $columnsAndDirections = static::explodeSortSpec($sort);
        $orderBy = [];

        foreach ($columnsAndDirections as $columnAndDirection) {
            list($column, $direction) = static::splitColumnAndDirection($columnAndDirection);

            $orderBy[] = [$column, $direction];
        }

        return $orderBy;
    }

    /**
     * Explode the given sort spec into its sort parts
     *
     * @param array|string $sort
     *
     * @return array
     */
    public static function explodeSortSpec($sort)
    {
        return Str::trimSplit(implode(',', (array) $sort));
    }

    /**
     * Normalize the given sort spec to a sort string
     *
     * @param array|string $sort
     *
     * @return string
     */
    public static function normalizeSortSpec($sort)
    {
        return implode(',', static::explodeSortSpec($sort));
    }

    /**
     * Explode the given sort part into its sort column and direction
     *
     * @param string $sort
     *
     * @return array
     */
    public static function splitColumnAndDirection($sort)
    {
        return Str::symmetricSplit($sort, ' ', 2);
    }
}
