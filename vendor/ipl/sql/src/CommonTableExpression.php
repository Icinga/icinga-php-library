<?php

namespace ipl\Sql;

/**
 * Implementation for the {@link CommonTableExpressionInterface} to allow CTEs via {@link with()}
 */
trait CommonTableExpression
{
    /**
     * All CTEs
     *
     * [
     *   [$query, $alias, $recursive],
     *   ...
     * ]
     *
     * @var array[]
     */
    protected $with = [];

    public function getWith()
    {
        return $this->with;
    }

    public function with(Select $query, $alias, $recursive = false)
    {
        $this->with[] = [$query, $alias, $recursive];

        return $this;
    }

    public function resetWith()
    {
        $this->with = [];

        return $this;
    }

    /**
     * Clone the properties provided by this trait
     *
     * Shall be called by using classes in their __clone()
     */
    protected function cloneCte()
    {
        foreach ($this->with as &$cte) {
            $cte[0] = clone $cte[0];
        }
        unset($cte);
    }
}
