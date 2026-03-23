<?php

namespace ipl\Sql;

/**
 * Implementation for the {@link WhereInterface}
 */
trait Where
{
    /** @var array|null Internal representation for the WHERE part of the query */
    protected $where;

    public function getWhere()
    {
        return $this->where;
    }

    public function where($condition, ...$args)
    {
        list($condition, $operator) = $this->prepareConditionArguments($condition, $args);
        $this->mergeCondition($this->where, $this->buildCondition($condition, $operator), Sql::ALL);

        return $this;
    }

    public function orWhere($condition, ...$args)
    {
        list($condition, $operator) = $this->prepareConditionArguments($condition, $args);
        $this->mergeCondition($this->where, $this->buildCondition($condition, $operator), Sql::ANY);

        return $this;
    }

    public function notWhere($condition, ...$args)
    {
        list($condition, $operator) = $this->prepareConditionArguments($condition, $args);
        $this->mergeCondition($this->where, $this->buildCondition($condition, $operator), Sql::NOT_ALL);

        return $this;
    }

    public function orNotWhere($condition, ...$args)
    {
        list($condition, $operator) = $this->prepareConditionArguments($condition, $args);
        $this->mergeCondition($this->where, $this->buildCondition($condition, $operator), Sql::NOT_ANY);

        return $this;
    }

    public function resetWhere()
    {
        $this->where = null;

        return $this;
    }

    /**
     * Make $condition an array and build an array like this: [$operator, [$condition]]
     *
     * If $condition is empty, replace it with a boolean constant depending on the operator.
     *
     * @param string|array $condition
     * @param string       $operator
     *
     * @return array
     */
    protected function buildCondition($condition, $operator)
    {
        if (is_array($condition)) {
            if (empty($condition)) {
                $condition = [$operator === Sql::ALL ? '1' : '0'];
            } elseif (in_array(reset($condition), [Sql::ALL, Sql::ANY, Sql::NOT_ALL, Sql::NOT_ANY], true)) {
                return $condition;
            }
        } else {
            $condition = [$condition];
        }

        return [$operator, $condition];
    }

    /**
     * Merge the given condition with ours via the given operator
     *
     * @param mixed  $base      Our condition
     * @param array  $condition As returned by {@link buildCondition()}
     * @param string $operator
     */
    protected function mergeCondition(&$base, array $condition, $operator)
    {
        if ($base === null) {
            $base = [$operator, [$condition]];
        } else {
            if ($base[0] === $operator) {
                $base[1][] = $condition;
            } elseif ($operator === Sql::NOT_ALL) {
                $base = [Sql::ALL, [$base, [$operator, [$condition]]]];
            } elseif ($operator === Sql::NOT_ANY) {
                $base = [Sql::ANY, [$base, [$operator, [$condition]]]];
            } else {
                $base = [$operator, [$base, $condition]];
            }
        }
    }

    /**
     * Prepare condition arguments from the different supported where styles
     *
     * @param mixed $condition
     * @param array $args
     *
     * @return array
     */
    protected function prepareConditionArguments($condition, array $args)
    {
        // Default operator
        $operator = Sql::ALL;

        if (! is_array($condition) && ! empty($args)) {
            // Variadic
            $condition = [(string) $condition => $args];
        } else {
            // Array or string format
            $operator = array_shift($args) ?: $operator;
        }

        return [$condition, $operator];
    }

    /**
     * Clone the properties provided by this trait
     *
     * Shall be called by using classes in their __clone()
     */
    protected function cloneWhere()
    {
        if ($this->where !== null) {
            $this->cloneCondition($this->where);
        }
    }

    /**
     * Clone a condition in-place
     *
     * @param array $condition As returned by {@link buildCondition()}
     */
    protected function cloneCondition(array &$condition)
    {
        foreach ($condition as &$subCondition) {
            if (is_array($subCondition)) {
                $this->cloneCondition($subCondition);
            } elseif ($subCondition instanceof ExpressionInterface || $subCondition instanceof Select) {
                $subCondition = clone $subCondition;
            }
        }
        unset($subCondition);
    }
}
