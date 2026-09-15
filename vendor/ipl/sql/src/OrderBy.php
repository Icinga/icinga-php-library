<?php

namespace ipl\Sql;

/**
 * Trait for the ORDER BY part of a query
 */
trait OrderBy
{
    /** @var ?array ORDER BY part of the query */
    protected ?array $orderBy = null;

    public function hasOrderBy(): bool
    {
        return $this->orderBy !== null;
    }

    public function getOrderBy(): ?array
    {
        return $this->orderBy;
    }

    public function orderBy(
        int|string|ExpressionInterface|Select|array $orderBy,
        int|string|null $direction = null
    ): static {
        if (! is_array($orderBy)) {
            $orderBy = [$orderBy];
        }

        foreach ($orderBy as $column => $dir) {
            if (is_int($column)) {
                $column = $dir;
                $dir = $direction;
            }

            if (is_array($column) && count($column) === 2) {
                [$column, $dir] = $column;
            }

            if ($dir === SORT_ASC) {
                $dir = 'ASC';
            } elseif ($dir === SORT_DESC) {
                $dir = 'DESC';
            }

            $this->orderBy[] = [$column, $dir];
        }

        return $this;
    }

    public function resetOrderBy(): static
    {
        $this->orderBy = null;

        return $this;
    }

    /**
     * Clone the properties provided by this trait
     *
     * Shall be called by using classes in their __clone()
     */
    protected function cloneOrderBy(): void
    {
        if ($this->orderBy !== null) {
            foreach ($this->orderBy as &$orderBy) {
                if ($orderBy[0] instanceof ExpressionInterface || $orderBy[0] instanceof Select) {
                    $orderBy[0] = clone $orderBy[0];
                }
            }
            unset($orderBy);
        }
    }
}
