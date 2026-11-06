<?php

namespace ipl\Orm;

use ArrayIterator;
use ipl\Orm\Contract\PersistBehavior;
use ipl\Orm\Contract\PropertyBehavior;
use ipl\Orm\Contract\RetrieveBehavior;
use ipl\Orm\Contract\RewriteColumnBehavior;
use ipl\Orm\Contract\RewriteFilterBehavior;
use ipl\Orm\Contract\RewritePathBehavior;
use ipl\Stdlib\Filter;
use IteratorAggregate;
use Traversable;

class Behaviors implements IteratorAggregate
{
    /** @var array Registered behaviors */
    protected array $behaviors = [];

    /** @var RetrieveBehavior[] Registered retrieve behaviors */
    protected array $retrieveBehaviors = [];

    /** @var PersistBehavior[] Registered persist behaviors */
    protected array $persistBehaviors = [];

    /** @var PropertyBehavior[] Registered property behaviors */
    protected array $propertyBehaviors = [];

    /** @var RewriteFilterBehavior[] Registered rewrite filter behaviors */
    protected array $rewriteFilterBehaviors = [];

    /** @var RewriteColumnBehavior[] Registered rewrite column behaviors */
    protected array $rewriteColumnBehaviors = [];

    /** @var RewritePathBehavior[] Registered rewrite path behaviors */
    protected array $rewritePathBehaviors = [];

    /**
     * Add a behavior
     *
     * @param Behavior $behavior
     *
     * @return void
     */
    public function add(Behavior $behavior): void
    {
        $this->behaviors[] = $behavior;

        if ($behavior instanceof PropertyBehavior) {
            $this->retrieveBehaviors[] = $behavior;
            $this->persistBehaviors[] = $behavior;
            $this->propertyBehaviors[] = $behavior;
        } else {
            if ($behavior instanceof RetrieveBehavior) {
                $this->retrieveBehaviors[] = $behavior;
            }

            if ($behavior instanceof PersistBehavior) {
                $this->persistBehaviors[] = $behavior;
            }
        }

        if ($behavior instanceof RewriteFilterBehavior) {
            $this->rewriteFilterBehaviors[] = $behavior;
        }

        if ($behavior instanceof RewriteColumnBehavior) {
            $this->rewriteColumnBehaviors[] = $behavior;
        }

        if ($behavior instanceof RewritePathBehavior) {
            $this->rewritePathBehaviors[] = $behavior;
        }
    }

    /**
     * Iterate registered behaviors
     *
     * @return ArrayIterator
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->behaviors);
    }

    /**
     * Apply all retrieve behaviors on the given model
     *
     * @param Model $model
     *
     * @return void
     */
    public function retrieve(Model $model): void
    {
        foreach ($this->retrieveBehaviors as $behavior) {
            $behavior->retrieve($model);
        }
    }

    /**
     * Apply all persist behaviors on the given model
     *
     * @param Model $model
     *
     * @return void
     */
    public function persist(Model $model): void
    {
        foreach ($this->persistBehaviors as $behavior) {
            $behavior->persist($model);
        }
    }

    /**
     * Transform the retrieved key's value by use of all property behaviors
     *
     * @param mixed  $value
     * @param string $key
     *
     * @return mixed
     */
    public function retrieveProperty(mixed $value, string $key): mixed
    {
        foreach ($this->propertyBehaviors as $behavior) {
            $value = $behavior->retrieveProperty($value, $key);
        }

        return $value;
    }

    /**
     * Transform the to be persisted key's value by use of all property behaviors
     *
     * @param mixed  $value
     * @param string $key
     *
     * @return mixed
     */
    public function persistProperty(mixed $value, string $key): mixed
    {
        foreach ($this->propertyBehaviors as $behavior) {
            $value = $behavior->persistProperty($value, $key);
        }

        return $value;
    }

    /**
     * Rewrite the given filter condition
     *
     * @param Filter\Condition $condition
     * @param ?string          $relation Absolute path (with a trailing dot) of the model
     *
     * @return Filter\Rule|null
     */
    public function rewriteCondition(Filter\Condition $condition, ?string $relation = null): ?Filter\Rule
    {
        $filter = null;
        foreach ($this->rewriteFilterBehaviors as $behavior) {
            $replacement = $behavior->rewriteCondition($filter ?: $condition, $relation);
            if ($replacement !== null) {
                $filter = $replacement;
            }
        }

        return $filter;
    }

    /**
     * Rewrite the given relation path
     *
     * @param string  $path
     * @param ?string $relation Absolute path of the model
     *
     * @return string|null
     */
    public function rewritePath(string $path, ?string $relation = null): ?string
    {
        $newPath = null;
        foreach ($this->rewritePathBehaviors as $behavior) {
            $replacement = $behavior->rewritePath($newPath ?: $path, $relation);
            if ($replacement !== null) {
                $newPath = $replacement;
            }
        }

        return $newPath;
    }

    /**
     * Rewrite the given column
     *
     * @param string  $column
     * @param ?string $relation Absolute path of the model
     *
     * @return mixed
     */
    public function rewriteColumn(string $column, ?string $relation = null): mixed
    {
        $newColumn = null;
        foreach ($this->rewriteColumnBehaviors as $behavior) {
            $replacement = $behavior->rewriteColumn($newColumn ?: $column, $relation);
            if ($replacement !== null) {
                $newColumn = $replacement;
            }
        }

        return $newColumn;
    }

    /**
     * Rewrite the given column definition
     *
     * @param ColumnDefinition $def
     * @param string $relation Absolute path of the model
     *
     * @return void
     */
    public function rewriteColumnDefinition(ColumnDefinition $def, string $relation): void
    {
        foreach ($this->rewriteColumnBehaviors as $behavior) {
            $behavior->rewriteColumnDefinition($def, $relation);
        }
    }

    /**
     * Get whether the given column is selectable
     *
     * @param string $column
     *
     * @return bool
     */
    public function isSelectableColumn(string $column): bool
    {
        foreach ($this->rewriteColumnBehaviors as $behavior) {
            if ($behavior->isSelectableColumn($column)) {
                return true;
            }
        }

        return false;
    }
}
