<?php

namespace ipl\Sql;

/**
 * Implementation for the {@link WhereInterface}
 */
trait Where
{
    /** @var ?array Internal representation for the WHERE part of the query */
    protected ?array $where = null;

    public function getWhere(): ?array
    {
        return $this->where;
    }

    public function where(string|ExpressionInterface|Select|array $condition, mixed ...$args): static
    {
        [$condition, $operator] = $this->prepareConditionArguments($condition, $args);
        $this->mergeCondition($this->where, $this->buildCondition($condition, $operator), Sql::ALL);

        return $this;
    }

    public function orWhere(string|ExpressionInterface|Select|array $condition, mixed ...$args): static
    {
        [$condition, $operator] = $this->prepareConditionArguments($condition, $args);
        $this->mergeCondition($this->where, $this->buildCondition($condition, $operator), Sql::ANY);

        return $this;
    }

    public function notWhere(string|ExpressionInterface|Select|array $condition, mixed ...$args): static
    {
        [$condition, $operator] = $this->prepareConditionArguments($condition, $args);
        $this->mergeCondition($this->where, $this->buildCondition($condition, $operator), Sql::NOT_ALL);

        return $this;
    }

    public function orNotWhere(string|ExpressionInterface|Select|array $condition, mixed ...$args): static
    {
        [$condition, $operator] = $this->prepareConditionArguments($condition, $args);
        $this->mergeCondition($this->where, $this->buildCondition($condition, $operator), Sql::NOT_ANY);

        return $this;
    }

    public function resetWhere(): static
    {
        $this->where = null;

        return $this;
    }

    /**
     * Make $condition an array and build an array like this: [$operator, [$condition]]
     *
     * If $condition is empty, replace it with a boolean constant depending on the operator.
     *
     * @param string|ExpressionInterface|Select|array $condition
     * @param string $operator
     *
     * @return array
     */
    protected function buildCondition(string|ExpressionInterface|Select|array $condition, string $operator): array
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
     * @param ?array $base Our condition
     * @param array $condition As returned by {@link buildCondition()}
     * @param string $operator
     */
    protected function mergeCondition(?array &$base, array $condition, string $operator): void
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
     * @param string|ExpressionInterface|Select|array $condition
     * @param array $args
     *
     * @return array
     */
    protected function prepareConditionArguments(string|ExpressionInterface|Select|array $condition, array $args): array
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
    protected function cloneWhere(): void
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
    protected function cloneCondition(array &$condition): void
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
