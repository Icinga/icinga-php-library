<?php

namespace ipl\Orm;

use InvalidArgumentException;
use ipl\Orm\Exception\InvalidRelationException;

/**
 * Hydrates raw database rows into concrete model instances.
 */
class Hydrator
{
    /** @var array Additional hydration rules for the model's relations */
    protected $hydrators = [];

    /** @var array<string, array<string, bool>> Map of columns to referencing paths */
    protected $columnToTargetMap = [];

    /** @var Query The query the hydration rules are for */
    protected $query;

    /**
     * Create a new Hydrator
     *
     * @param Query $query
     */
    public function __construct(Query $query)
    {
        $this->query = $query;
    }

    /**
     * Add a hydration rule
     *
     * @param string $path Model path
     *
     * @return $this
     *
     * @throws \InvalidArgumentException If a hydrator for the given path already exists
     */
    public function add($path)
    {
        if (isset($this->hydrators[$path])) {
            throw new \InvalidArgumentException("Hydrator for path '$path' already exists");
        }

        $resolver = $this->query->getResolver();
        $target = $this->query->getModel();
        $relation = null;

        if ($path === $target->getTableAlias()) {
            $selectableColumns = $resolver->getSelectableColumns($target);
            $columnToPropertyMap = array_combine($selectableColumns, $selectableColumns);
        } else {
            $relation = $resolver->resolveRelation($path);
            $target = $relation->getTarget();
            $selectableColumns = $resolver->getSelectableColumns($target);
            $columnToPropertyMap = array_combine(
                array_keys($resolver->qualifyColumnsAndAliases($selectableColumns, $target)),
                $selectableColumns
            );
        }

        $relationLoader = function (Model $subject, string $relationName) {
            return $this->query->derive($relationName, $subject);
        };

        $defaults = $this->query->getResolver()->getDefaults($target);
        foreach ($resolver->getRelations($target) as $targetRelation) {
            $targetRelationName = $targetRelation->getName();
            if (! $defaults->has($targetRelationName)) {
                $defaults->add($targetRelationName, $relationLoader);
            }
        }

        $this->updateColumnToTargetMap($path, $columnToPropertyMap);
        $this->hydrators[$path] = [$target, $relation, $columnToPropertyMap, $defaults];

        return $this;
    }

    /**
     * Update which columns the given path is referencing
     *
     * @param string $path
     * @param array<string, string> $columnToPropertyMap
     *
     * @return void
     */
    protected function updateColumnToTargetMap(string $path, array $columnToPropertyMap): void
    {
        foreach ($columnToPropertyMap as $qualifiedColumnPath => $_) {
            if (isset($this->columnToTargetMap[$qualifiedColumnPath])) {
                $this->columnToTargetMap[$qualifiedColumnPath][$path] = true;
            } else {
                $this->columnToTargetMap[$qualifiedColumnPath] = [$path => true];
            }
        }
    }

    /**
     * Hydrate the given raw database rows into the specified model
     *
     * @param array $data
     * @param Model $model
     *
     * @return Model
     */
    public function hydrate(array $data, Model $model)
    {
        $defaultsToApply = [];
        $columnToTargetMap = $this->columnToTargetMap;
        foreach ($this->hydrators as $path => $vars) {
            list($target, $relation, $columnToPropertyMap, $defaults) = $vars;

            $subject = $model;
            if ($relation !== null) {
                /** @var Relation $relation */

                $steps = explode('.', $path);
                $baseTable = array_shift($steps);
                $relationName = array_pop($steps);

                $parent = $model;
                foreach ($steps as $i => $step) {
                    if (! isset($parent->$step)) {
                        $intermediateRelation = $this->query->getResolver()->resolveRelation(
                            $baseTable . '.' . implode('.', array_slice($steps, 0, $i + 1)),
                            $model
                        );
                        $parentClass = $intermediateRelation->getTargetClass();
                        $parent = $parent->$step = new $parentClass();
                    } else {
                        $parent = $parent->$step;
                    }
                }

                if (isset($parent->$relationName)) {
                    $subject = $parent->$relationName;
                } else {
                    $subjectClass = $relation->getTargetClass();
                    $subject = new $subjectClass();
                    $parent->$relationName = $subject;
                }
            }

            $subject->setProperties($this->extractAndMap($data, $columnToPropertyMap, $path, $columnToTargetMap));
            $this->query->getResolver()->getBehaviors($target)->retrieve($subject);
            $defaultsToApply[] = [$subject, $defaults];
        }

        // If there are any columns left, propagate them to the targeted relation if possible, to the base otherwise
        foreach ($data as $column => $value) {
            if (($aliasPrefix = $this->query->getResolver()->getAliasPrefix())) {
                $column = substr($column, strlen($aliasPrefix));
            }

            $columnName = $column;
            $steps = explode('_', $column);
            $baseTable = array_shift($steps);
            while (! empty($steps) && $baseTable !== $model->getTableAlias()) {
                $baseTable .= '_' . array_shift($steps);
            }

            $subject = $model;
            $target = $this->query->getModel();
            $stepsTaken = [];
            for ($i = 0; $i < count($steps); $i++) {
                $step = $steps[$i];
                $stepsTaken[] = $step;
                $relationPath = "$baseTable." . implode('.', $stepsTaken);

                try {
                    $relation = $this->query->getResolver()->resolveRelation($relationPath);
                } catch (InvalidRelationException $_) {
                    if (isset($steps[$i + 1])) {
                        $steps[$i + 1] = $step . '_' . $steps[$i + 1];
                        array_pop($stepsTaken);
                        continue;
                    } else {
                        array_pop($stepsTaken);
                        $columnName = implode('_', array_slice($steps, $i));
                        break;
                    }
                }

                if (! $subject->hasProperty($step)) {
                    $stepClass = $relation->getTargetClass();
                    $subject->$step = new $stepClass();
                }

                $subject = $subject->$step;
                $target = $relation->getTarget();
            }

            $subject->$columnName = $this->query
                ->getResolver()
                ->getBehaviors($target)
                ->retrieveProperty($value, $columnName);
        }

        // Apply defaults last, otherwise we may evaluate them during hydration
        foreach ($defaultsToApply as list($subject, $defaults)) {
            foreach ($defaults as $name => $default) {
                if (! $subject->hasProperty($name)) {
                    $subject->$name = $default;
                }
            }
        }

        return $model;
    }

    /**
     * Extract and map the given data based on the specified column to property resolution map
     *
     * @param array $data
     * @param array $columnToPropertyMap
     * @param string $path
     * @param array<string, array<string, bool>> $columnToTargetMap
     *
     * @return array
     */
    protected function extractAndMap(array &$data, array $columnToPropertyMap, string $path, array &$columnToTargetMap)
    {
        $extracted = [];
        foreach (array_intersect_key($columnToPropertyMap, $data) as $column => $property) {
            $extracted[$property] = $data[$column];

            if (isset($columnToTargetMap[$column][$path])) {
                unset($columnToTargetMap[$column][$path]);
                if (empty($columnToTargetMap[$column])) {
                    // Only unset a column once it's really not required anymore
                    unset($data[$column], $columnToTargetMap[$column]);
                }
            }
        }

        return $extracted;
    }
}
