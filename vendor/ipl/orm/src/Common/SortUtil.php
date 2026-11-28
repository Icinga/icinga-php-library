<?php

namespace ipl\Orm\Common;

use ipl\Stdlib\Str;

class SortUtil
{
    /**
     * Create the sort column(s) and direction(s) from the given sort spec
     *
     * @param string|array $sort
     *
     * @return array<int, mixed> Sort column(s) and direction(s) suitable for {@link OrderByInterface::orderBy()}
     */
    public static function createOrderBy(string|array $sort): array
    {
        $columnsAndDirections = static::explodeSortSpec($sort);
        $orderBy = [];

        foreach ($columnsAndDirections as $columnAndDirection) {
            [$column, $direction] = static::splitColumnAndDirection($columnAndDirection);

            $orderBy[] = [$column, $direction];
        }

        return $orderBy;
    }

    /**
     * Explode the given sort spec into its sort parts
     *
     * @param string|array $sort
     *
     * @return array
     */
    public static function explodeSortSpec(string|array $sort): array
    {
        return Str::trimSplit(implode(',', (array) $sort));
    }

    /**
     * Normalize the given sort spec to a sort string
     *
     * @param string|array $sort
     *
     * @return string
     */
    public static function normalizeSortSpec(string|array $sort): string
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
    public static function splitColumnAndDirection(string $sort): array
    {
        return Str::symmetricSplit($sort, ' ', 2);
    }
}
