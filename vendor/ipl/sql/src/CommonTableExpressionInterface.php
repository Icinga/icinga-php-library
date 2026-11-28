<?php

namespace ipl\Sql;

/**
 * Interface for CTEs via {@link with()}
 */
interface CommonTableExpressionInterface
{
    /**
     * Get all CTEs
     *
     * [
     *   [$query, $alias, $columns, $recursive],
     *   ...
     * ]
     *
     * @return array[]
     */
    public function getWith(): array;

    /**
     * Add a CTE
     *
     * @param Select $query
     * @param string $alias
     * @param bool $recursive
     *
     * @return $this
     */
    public function with(Select $query, string $alias, bool $recursive = false): static;

    /**
     * Reset all CTEs
     *
     * @return $this
     */
    public function resetWith(): static;
}
