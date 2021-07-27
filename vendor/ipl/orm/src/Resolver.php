<?php

namespace ipl\Orm;

use Generator;
use InvalidArgumentException;
use ipl\Orm\Relation\BelongsToMany;
use ipl\Orm\Relation\HasOne;
use ipl\Sql\ExpressionInterface;
use OutOfBoundsException;
use RuntimeException;
use SplObjectStorage;

/**
 * Column and relation resolver. Acts as glue between queries and models
 */
class Resolver
{
    /** @var Query The query to resolve */
    protected $query;

    /** @var SplObjectStorage Model relations */
    protected $relations;

    /** @var SplObjectStorage Model behaviors */
    protected $behaviors;

    /** @var SplObjectStorage Model aliases */
    protected $aliases;

    /** @var string The alias prefix to use */
    protected $aliasPrefix;

    /** @var SplObjectStorage Selectable columns from resolved models */
    protected $selectableColumns;

    /** @var SplObjectStorage Select columns from resolved models */
    protected $selectColumns;

    /** @var SplObjectStorage Meta data from models and their direct relations */
    protected $metaData;

    /** @var SplObjectStorage Resolved relations */
    protected $resolvedRelations;

    /**
     * Create a new resolver
     */
    public function __construct()
    {
        $this->relations = new SplObjectStorage();
        $this->behaviors = new SplObjectStorage();
        $this->aliases = new SplObjectStorage();
        $this->selectableColumns = new SplObjectStorage();
        $this->selectColumns = new SplObjectStorage();
        $this->metaData = new SplObjectStorage();
        $this->resolvedRelations = new SplObjectStorage();
    }

    /**
     * Set the query this resolver belongs to
     *
     * @param Query $query
     *
     * @return $this
     */
    public function setQuery(Query $query)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Get a model's relations
     *
     * @param Model $model
     *
     * @return Relations
     */
    public function getRelations(Model $model)
    {
        if (! $this->relations->contains($model)) {
            $relations = new Relations();
            $model->createRelations($relations);
            $this->relations->attach($model, $relations);
        }

        return $this->relations[$model];
    }

    /**
     * Get a model's behaviors
     *
     * @param Model $model
     *
     * @return Behaviors
     */
    public function getBehaviors(Model $model)
    {
        if (! $this->behaviors->contains($model)) {
            $behaviors = new Behaviors();
            $model->createBehaviors($behaviors);
            $this->behaviors->attach($model, $behaviors);
        }

        return $this->behaviors[$model];
    }

    /**
     * Get a model alias
     *
     * @param Model $model
     *
     * @return string
     *
     * @throws OutOfBoundsException If no alias exists for the given model
     */
    public function getAlias(Model $model)
    {
        if (! $this->aliases->contains($model)) {
            throw new OutOfBoundsException(sprintf(
                "Can't get alias for model '%s'. Alias does not exist",
                get_class($model)
            ));
        }

        return $this->aliasPrefix . $this->aliases[$model];
    }

    /**
     * Set a model alias
     *
     * @param Model  $model
     * @param string $alias
     *
     * @return $this
     */
    public function setAlias(Model $model, $alias)
    {
        $this->aliases[$model] = $alias;

        return $this;
    }

    /**
     * Get the alias prefix
     *
     * @return string
     */
    public function getAliasPrefix()
    {
        return $this->aliasPrefix;
    }

    /**
     * Set the alias prefix
     *
     * @param string $alias
     *
     * @return $this
     */
    public function setAliasPrefix($alias)
    {
        $this->aliasPrefix = $alias;

        return $this;
    }

    /**
     * Get whether the specified model provides the given selectable column
     *
     * @param Model  $subject
     * @param string $column
     *
     * @return bool
     */
    public function hasSelectableColumn(Model $subject, $column)
    {
        if (! $this->selectableColumns->contains($subject)) {
            $this->collectColumns($subject);
        }

        $columns = $this->selectableColumns[$subject];

        return isset($columns[$column]);
    }

    /**
     * Get all selectable columns from the given model
     *
     * @param Model $subject
     *
     * @return array
     */
    public function getSelectableColumns(Model $subject)
    {
        if (! $this->selectableColumns->contains($subject)) {
            $this->collectColumns($subject);
        }

        return array_keys($this->selectableColumns[$subject]);
    }

    /**
     * Get all select columns from the given model
     *
     * @param Model $subject
     *
     * @return array Select columns suitable for {@link \ipl\Sql\Select::columns()}
     */
    public function getSelectColumns(Model $subject)
    {
        if (! $this->selectColumns->contains($subject)) {
            $this->collectColumns($subject);
        }

        return $this->selectColumns[$subject];
    }

    /**
     * Get all meta data from the given model and its direct relations
     *
     * @param Model $subject
     *
     * @return array Column paths as keys (relative to $subject) and their meta data as values
     */
    public function getMetaData(Model $subject)
    {
        if (! $this->metaData->contains($subject)) {
            $this->metaData->attach($subject, $this->collectMetaData($subject));
        }

        return $this->metaData[$subject];
    }

    /**
     * Qualify the given alias by the specified table name
     *
     * @param string $alias
     * @param string $tableName
     *
     * @return string
     */
    public function qualifyColumnAlias($alias, $tableName)
    {
        return $tableName . '_' . $alias;
    }

    /**
     * Qualify the given column by the specified table name
     *
     * @param string $column
     * @param string $tableName
     *
     * @return string
     */
    public function qualifyColumn($column, $tableName)
    {
        return $tableName . '.' . $column;
    }

    /**
     * Qualify the given columns and aliases by the specified model
     *
     * @param array $columns
     * @param Model $model
     * @param bool $autoAlias Set an alias for columns which have none
     *
     * @return array
     */
    public function qualifyColumnsAndAliases(array $columns, Model $model, $autoAlias = true)
    {
        $modelAlias = $this->getAlias($model);

        $qualified = [];
        foreach ($columns as $alias => $column) {
            if (is_int($alias)) {
                // TODO: Provide an alias for expressions nonetheless? (One without won't be hydrated)
                if ($autoAlias && ! $column instanceof ExpressionInterface) {
                    $alias = $this->qualifyColumnAlias($column, $modelAlias);
                }
            } elseif (($dot = strrpos($alias, '.')) !== false) {
                $modelAlias = $this->getAlias(
                    $this->resolveRelation(substr($alias, 0, $dot), $model)->getTarget()
                );
                $alias = $this->qualifyColumnAlias(substr($alias, $dot + 1), $modelAlias);
            }

            if ($column instanceof ExpressionInterface) {
                $column->setColumns($this->qualifyColumnsAndAliases($column->getColumns(), $model, $autoAlias));
            } elseif (($dot = strrpos($column, '.')) !== false) {
                $modelAlias = $this->getAlias(
                    $this->resolveRelation(substr($column, 0, $dot), $model)->getTarget()
                );
                $column = $this->qualifyColumn(substr($column, $dot + 1), $modelAlias);
            } else {
                $column = $this->qualifyColumn($column, $modelAlias);
            }

            $qualified[$alias] = $column;
        }

        return $qualified;
    }

    /**
     * Qualify the given path by the specified table name
     *
     * @param string $path
     * @param string $tableName
     *
     * @return string
     */
    public function qualifyPath($path, $tableName)
    {
        $segments = explode('.', $path, 2);

        if ($segments[0] !== $tableName) {
            array_unshift($segments, $tableName);
        }

        $path = implode('.', $segments);

        return $path;
    }

    /**
     * Get whether the given relation path points to a distinct entity
     *
     * @param string $path
     *
     * @return bool
     */
    public function isDistinctRelation($path)
    {
        foreach ($this->resolveRelations($path) as $relation) {
            if (! $relation->isOne()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Resolve the rightmost relation of the given path
     *
     * Also resolves all other relations.
     *
     * @param string $path
     * @param Model  $subject
     *
     * @return Relation
     */
    public function resolveRelation($path, Model $subject = null)
    {
        $subject = $subject ?: $this->query->getModel();
        if (! $this->resolvedRelations->contains($subject) || ! isset($this->resolvedRelations[$subject][$path])) {
            foreach ($this->resolveRelations($path, $subject) as $_) {
                // run and exhaust generator
            }
        }

        return $this->resolvedRelations[$subject][$path];
    }

    /**
     * Resolve all relations of the given path
     *
     * Traverses the entire path and yields the path travelled so far as key and the relation as value.
     *
     * @param string $path
     * @param Model  $subject
     *
     * @return Generator
     * @throws InvalidArgumentException In case $path is not fully qualified or a relation is unknown
     */
    public function resolveRelations($path, Model $subject = null)
    {
        $relations = explode('.', $path);
        $subject = $subject ?: $this->query->getModel();

        if ($relations[0] !== $subject->getTableName()) {
            throw new InvalidArgumentException(sprintf(
                'Cannot resolve relation path "%s". Base table name is missing.',
                $path
            ));
        }

        $resolvedRelations = [];
        if ($this->resolvedRelations->contains($subject)) {
            $resolvedRelations = $this->resolvedRelations[$subject];
        }

        $target = $subject;
        $segments = [array_shift($relations)];
        foreach ($relations as $relationName) {
            $segments[] = $relationName;
            $relationPath = join('.', $segments);

            if (isset($resolvedRelations[$relationPath])) {
                $relation = $resolvedRelations[$relationPath];
            } else {
                $targetRelations = $this->getRelations($target);
                if (! $targetRelations->has($relationName)) {
                    throw new InvalidArgumentException(sprintf(
                        'Cannot join relation "%s" in model "%s". Relation not found.',
                        $relationName,
                        get_class($target)
                    ));
                }

                $relation = $targetRelations->get($relationName);
                $relation->setSource($target);

                $resolvedRelations[$relationPath] = $relation;

                if ($relation instanceof BelongsToMany) {
                    $through = $relation->getThrough();
                    $this->setAlias($through, join('_', array_merge(
                        array_slice($segments, 0, -1),
                        [$through->getTableName()]
                    )));
                }

                $this->setAlias($relation->getTarget(), join('_', $segments));
            }

            yield $relationPath => $relation;

            $target = $relation->getTarget();
        }

        $this->resolvedRelations->attach($subject, $resolvedRelations);
    }

    /**
     * Require and resolve columns
     *
     * Related models will be automatically added for eager-loading.
     *
     * @param array $columns
     *
     * @return Generator
     *
     * @throws RuntimeException If a column does not exist
     */
    public function requireAndResolveColumns(array $columns)
    {
        $model = $this->query->getModel();
        $tableName = $model->getTableName();

        foreach ($columns as $alias => $column) {
            if ($column instanceof ExpressionInterface) {
                foreach ($this->requireAndResolveColumns($column->getColumns()) as $_) {
                    // Only the expression itself is part of the select
                }
            }

            if ($column === '*' || $column instanceof ExpressionInterface) {
                yield [$model, $alias, $column];

                continue;
            }

            $dot = strrpos($column, '.');

            switch (true) {
                /** @noinspection PhpMissingBreakStatementInspection */
                case $dot !== false:
                    $relation = substr($column, 0, $dot);
                    $column = substr($column, $dot + 1);

                    if ($relation !== $tableName) {
                        $relation = $this->qualifyPath($relation, $tableName);

                        $this->query->with($relation);
                        $target = $this->resolvedRelations[$model][$relation]->getTarget();

                        break;
                    }
                // Move to default
                default:
                    $relation = null;
                    $target = $model;
            }

            if (! $this->hasSelectableColumn($target, $column)) {
                throw new RuntimeException(sprintf(
                    "Can't require column '%s' in model '%s'. Column not found.",
                    $column,
                    get_class($target)
                ));
            }

            yield [$target, $alias, $column];
        }
    }

    /**
     * Require all remaining columns that are not already selected
     *
     * @param array $existingColumns The fully qualified columns that are already selected
     * @param Model $model The model from which to fetch any remaining columns
     *
     * @return array
     */
    public function requireRemainingColumns(array $existingColumns, Model $model)
    {
        $modelColumns = $this->getSelectColumns($model);
        if (empty($existingColumns)) {
            return $modelColumns;
        }

        $modelAlias = $this->getAlias($model);
        $isBaseModel = $model === $this->query->getModel();

        foreach ($existingColumns as $alias => $columnPath) {
            if (is_string($alias)) {
                if ($isBaseModel || substr($alias, 0, strlen($modelAlias)) === $modelAlias) {
                    if (! $isBaseModel) {
                        $alias = substr($alias, strlen($modelAlias) + 1);
                    }

                    if (isset($modelColumns[$alias])) {
                        unset($modelColumns[$alias]);
                        continue;
                    }
                } else {
                    continue;
                }
            }

            if (is_string($columnPath) && substr($columnPath, 0, strlen($modelAlias)) === $modelAlias) {
                $column = substr($columnPath, strlen($modelAlias) + 1);
                if (($pos = array_search($column, $modelColumns, true)) !== false) {
                    if (is_int($pos)) {
                        // Explicit aliases can only be overridden with the same alias (see above)
                        unset($modelColumns[$pos]);
                        continue;
                    }
                }
            }

            // Not an alias match nor a column match. The only remaining match can be
            // accomplished by checking whether a selected alias can be mapped to a
            // column of the model.
            if (is_string($alias) && ($pos = array_search($alias, $modelColumns, true)) !== false) {
                if (is_int($pos)) {
                    unset($modelColumns[$pos]);
                }
            }
        }

        return $modelColumns;
    }

    /**
     * Collect all selectable columns from the given model
     *
     * @param Model $subject
     */
    protected function collectColumns(Model $subject)
    {
        // Don't fail if Model::getColumns() also contains the primary key columns
        $columns = array_merge((array) $subject->getKeyName(), (array) $subject->getColumns());

        $this->selectColumns->attach($subject, $columns);

        $selectable = [];

        foreach ($columns as $alias => $column) {
            if (is_int($alias)) {
                $selectable[$column] = true;
            } else {
                $selectable[$alias] = true;
            }
        }

        $this->selectableColumns->attach($subject, $selectable);
    }

    /**
     * Collect all meta data from the given model and its direct relations
     *
     * @param Model $subject
     *
     * @return array
     */
    protected function collectMetaData(Model $subject)
    {
        if ($subject instanceof UnionModel) {
            $models = [];
            foreach ($subject->getUnions() as $union) {
                /** @var Model $unionModel */
                $unionModel = new $union[0]();
                $models[$unionModel->getTableName()] = $unionModel;
                $this->collectDirectRelations($unionModel, $models, []);
            }
        } else {
            $models = [$subject->getTableName() => $subject];
            $this->collectDirectRelations($subject, $models, []);
        }

        $columns = [];
        foreach ($models as $path => $model) {
            /** @var Model $model */
            foreach ($model->getMetaData() as $columnName => $columnMeta) {
                $columns[$path . '.' . $columnName] = $columnMeta;
            }
        }

        return $columns;
    }

    /**
     * Collect all direct relations of the given model
     *
     * A direct relation is either a direct descendant of the model
     * or a descendant of such related in a to-one cardinality.
     *
     * @param Model $subject
     * @param array $models
     * @param array $path
     */
    protected function collectDirectRelations(Model $subject, array &$models, array $path)
    {
        foreach ($this->getRelations($subject) as $name => $relation) {
            /** @var Relation $relation */
            $isOne = $relation instanceof HasOne;
            if (empty($path) || $isOne) {
                $relationPath = [$name];
                if ($isOne && empty($path)) {
                    array_unshift($relationPath, $subject->getTableName());
                }

                $relationPath = array_merge($path, $relationPath);
                $models[join('.', $relationPath)] = $relation->getTarget();
                $this->collectDirectRelations($relation->getTarget(), $models, $relationPath);
            }
        }
    }
}
