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
    protected $behaviors = [];

    /** @var RetrieveBehavior[] Registered retrieve behaviors */
    protected $retrieveBehaviors = [];

    /** @var PersistBehavior[] Registered persist behaviors */
    protected $persistBehaviors = [];

    /** @var PropertyBehavior[] Registered property behaviors */
    protected $propertyBehaviors = [];

    /** @var RewriteFilterBehavior[] Registered rewrite filter behaviors */
    protected $rewriteFilterBehaviors = [];

    /** @var RewriteColumnBehavior[] Registered rewrite column behaviors */
    protected $rewriteColumnBehaviors = [];

    /** @var RewritePathBehavior[] Registered rewrite path behaviors */
    protected $rewritePathBehaviors = [];

    /**
     * Add a behavior
     *
     * @param PersistBehavior|PropertyBehavior|RetrieveBehavior|RewriteFilterBehavior $behavior
     */
    public function add(Behavior $behavior)
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
     */
    public function retrieve(Model $model)
    {
        foreach ($this->retrieveBehaviors as $behavior) {
            $behavior->retrieve($model);
        }
    }

    /**
     * Apply all persist behaviors on the given model
     *
     * @param Model $model
     */
    public function persist(Model $model)
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
    public function retrieveProperty($value, $key)
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
    public function persistProperty($value, $key)
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
     * @param string           $relation Absolute path (with a trailing dot) of the model
     *
     * @return Filter\Rule|null
     */
    public function rewriteCondition(Filter\Condition $condition, $relation = null)
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
     * @param string $path
     * @param string $relation Absolute path of the model
     *
     * @return string|null
     */
    public function rewritePath($path, $relation = null)
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
     * @param string $column
     * @param string $relation Absolute path of the model
     *
     * @return mixed
     */
    public function rewriteColumn($column, $relation = null)
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
