<?php

namespace ipl\Orm;

use AppendIterator;
use ArrayIterator;
use Generator;
use InvalidArgumentException;
use ipl\Orm\Contract\QueryAwareBehavior;
use ipl\Orm\Exception\InvalidColumnException;
use ipl\Orm\Exception\InvalidRelationException;
use ipl\Orm\Relation\BelongsToMany;
use ipl\Sql\ExpressionInterface;
use LogicException;
use OutOfBoundsException;
use SplObjectStorage;

use function ipl\Stdlib\get_php_type;

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

    /** @var SplObjectStorage Model defaults */
    protected $defaults;

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
     *
     * @param Query $query The query to resolve
     */
    public function __construct(Query $query)
    {
        $this->query = $query;

        $this->relations = new SplObjectStorage();
        $this->behaviors = new SplObjectStorage();
        $this->defaults = new SplObjectStorage();
        $this->aliases = new SplObjectStorage();
        $this->selectableColumns = new SplObjectStorage();
        $this->selectColumns = new SplObjectStorage();
        $this->metaData = new SplObjectStorage();
        $this->resolvedRelations = new SplObjectStorage();
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

            foreach ($behaviors as $behavior) {
                if ($behavior instanceof QueryAwareBehavior) {
                    $behavior->setQuery($this->query);
                }
            }
        }

        return $this->behaviors[$model];
    }

    /**
     * Get a model's defaults
     *
     * @param Model $model
     *
     * @return Defaults
     */
    public function getDefaults(Model $model): Defaults
    {
        if (! $this->defaults->contains($model)) {
            $defaults = new Defaults($this->query);
            $model->createDefaults($defaults);
            $this->defaults->attach($model, $defaults);
        }

        return $this->defaults[$model];
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
        if (! isset($columns[$column])) {
            $columns[$column] = $this->getBehaviors($subject)->isSelectableColumn($column);
        }

        return $columns[$column];
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
    public function getColumnDefinitions(Model $subject)
    {
        if (! $this->metaData->contains($subject)) {
            $this->metaData->attach($subject, $this->collectMetaData($subject));
        }

        return $this->metaData[$subject];
    }

    /**
     * Get definition of the given column
     *
     * @param string $columnPath
     *
     * @return ColumnDefinition
     */
    public function getColumnDefinition(string $columnPath): ColumnDefinition
    {
        $parts = explode('.', $columnPath);
        $model = $this->query->getModel();

        if ($parts[0] !== $model->getTableAlias()) {
            array_unshift($parts, $model->getTableAlias());
        }

        do {
            $relationPath[] = array_shift($parts);
            $column = implode('.', $parts);

            if (count($relationPath) === 1) {
                $subject = $model;
            } else {
                $subject = $this->resolveRelation(implode('.', $relationPath))->getTarget();
            }

            if ($this->hasSelectableColumn($subject, $column)) {
                break;
            }
        } while ($parts);

        $definition = $this->getColumnDefinitions($subject)[$column] ?? new ColumnDefinition($column);
        $this->getBehaviors($subject)->rewriteColumnDefinition($definition, implode('.', $relationPath));

        return $definition;
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
     * Qualify the given columns by the specified model
     *
     * @param iterable $columns
     * @param Model $model Leave null in case $columns is {@see Resolver::requireAndResolveColumns()}
     *
     * @return array
     *
     * @throws InvalidArgumentException If $columns is not iterable
     * @throws InvalidArgumentException If $model is not passed and $columns is not a generator
     */
    public function qualifyColumns($columns, Model $model = null)
    {
        $target = $model ?: $this->query->getModel();
        $targetAlias = $this->getAlias($target);

        if (! is_iterable($columns)) {
            throw new InvalidArgumentException(
                sprintf('$columns is not iterable, got %s instead', get_php_type($columns))
            );
        }

        $qualified = [];
        foreach ($columns as $alias => $column) {
            if (is_int($alias) && is_array($column)) {
                // $columns is $this->requireAndResolveColumns()
                list($target, $alias, $columnName) = $column;
                $targetAlias = $this->getAlias($target);

                // Thanks to PHP 5.6 where `list` is evaluated from right to left. It will extract
                // the values for `$target` and `$alias` then from the third argument (`$column`).
                $column = $columnName;
            } elseif ($target === null) {
                throw new InvalidArgumentException(
                    'Passing no model is only possible if $columns is a generator'
                );
            }

            if ($column instanceof ResolvedExpression) {
                $column->setColumns($this->qualifyColumns($column->getResolvedColumns()));
            } elseif ($column instanceof ExpressionInterface) {
                $column = clone $column; // The expression may be part of a model and those shouldn't change implicitly
                $column->setColumns($this->qualifyColumns($column->getColumns(), $target));
            } else {
                $column = $this->qualifyColumn($column, $targetAlias);
            }

            $qualified[$alias] = $column;
        }

        return $qualified;
    }

    /**
     * Qualify the given columns and aliases by the specified model
     *
     * @param iterable $columns
     * @param Model $model Leave null in case $columns is {@see Resolver::requireAndResolveColumns()}
     * @param bool $autoAlias Set an alias for columns which have none
     *
     * @return array
     *
     * @throws InvalidArgumentException If $columns is not iterable
     * @throws InvalidArgumentException If $model is not passed and $columns is not a generator
     */
    public function qualifyColumnsAndAliases($columns, Model $model = null, $autoAlias = true)
    {
        $target = $model ?: $this->query->getModel();
        $targetAlias = $this->getAlias($target);

        if (! is_iterable($columns)) {
            throw new InvalidArgumentException(
                sprintf('$columns is not iterable, got %s instead', get_php_type($columns))
            );
        }

        $qualified = [];
        foreach ($columns as $alias => $column) {
            if (is_int($alias) && is_array($column)) {
                // $columns is $this->requireAndResolveColumns()
                list($target, $alias, $columnName) = $column;
                $targetAlias = $this->getAlias($target);

                // Thanks to PHP 5.6 where `list` is evaluated from right to left. It will extract
                // the values for `$target` and `$alias` then from the third argument (`$column`).
                $column = $columnName;
            } elseif ($target === null) {
                throw new InvalidArgumentException(
                    'Passing no model is only possible if $columns is a generator'
                );
            }

            if (is_int($alias)) {
                if ($column instanceof AliasedExpression) {
                    $alias = $column->getAlias();
                } elseif ($autoAlias && ! $column instanceof ExpressionInterface) {
                    $alias = $this->qualifyColumnAlias($column, $targetAlias);
                }
            } elseif ($target !== $this->query->getModel()) {
                if (strpos($alias, '.') !== false) {
                    // This is safe, because custom aliases won't be qualified
                    $alias = str_replace('.', '_', $alias);
                } else {
                    $alias = $this->qualifyColumnAlias($alias, $targetAlias);
                }
            }

            if ($column instanceof ResolvedExpression) {
                $column->setColumns($this->qualifyColumns($column->getResolvedColumns()));
            } elseif ($column instanceof ExpressionInterface) {
                $column = clone $column; // The expression may be part of a model and those shouldn't change implicitly
                $column->setColumns($this->qualifyColumns($column->getColumns(), $target));
            } else {
                $column = $this->qualifyColumn($column, $targetAlias);
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
     * @throws InvalidArgumentException In case $path is not fully qualified
     * @throws InvalidRelationException In case a relation is unknown
     */
    public function resolveRelations($path, Model $subject = null)
    {
        $relations = explode('.', $path);
        $subject = $subject ?: $this->query->getModel();

        if ($relations[0] !== $subject->getTableAlias()) {
            throw new InvalidArgumentException(sprintf(
                'Cannot resolve relation path "%s". Base table alias/name is missing.',
                $path
            ));
        }

        $resolvedRelations = [];
        if ($this->resolvedRelations->contains($subject)) {
            $resolvedRelations = $this->resolvedRelations[$subject];
        }

        $target = $subject;
        $pathBeingResolved = null;
        $segments = [array_shift($relations)];
        while (! empty($relations)) {
            $newPath = $this->getBehaviors($target)
                ->rewritePath(join('.', $relations), join('.', $segments));
            if ($newPath !== null) {
                $relations = explode('.', $newPath);
                $pathBeingResolved = $path;
            }

            $relationName = array_shift($relations);
            $segments[] = $relationName;
            $relationPath = join('.', $segments);

            if (isset($resolvedRelations[$relationPath])) {
                $relation = $resolvedRelations[$relationPath];
            } else {
                $targetRelations = $this->getRelations($target);
                if (! $targetRelations->has($relationName)) {
                    throw new InvalidRelationException($relationName, $target);
                }

                $relation = $targetRelations->get($relationName);
                $relation->setSource($target);

                $resolvedRelations[$relationPath] = $relation;

                if ($relation instanceof BelongsToMany) {
                    $through = $relation->getThrough();
                    $this->setAlias($through, join('_', array_merge(
                        array_slice($segments, 0, -1),
                        [$through->getTableAlias()]
                    )));
                }

                $this->setAlias($relation->getTarget(), join('_', $segments));
            }

            yield $relationPath => $relation;

            $target = $relation->getTarget();
        }

        if ($pathBeingResolved !== null) {
            $resolvedRelations[$pathBeingResolved] = $relation;
        }

        $this->resolvedRelations->attach($subject, $resolvedRelations);
    }

    /**
     * Require and resolve columns
     *
     * Related models will be automatically added for eager-loading.
     *
     * @param array $columns
     * @param Model $model
     *
     * @return Generator
     *
     * @throws InvalidColumnException If a column does not exist
     */
    public function requireAndResolveColumns(array $columns, Model $model = null)
    {
        $model = $model ?: $this->query->getModel();
        $tableName = $model->getTableAlias();

        $baseTableColumns = [];
        foreach ($columns as $alias => $column) {
            $columnPath = &$column;
            if ($column instanceof ExpressionInterface) {
                $column = new ResolvedExpression(
                    $column,
                    $this->requireAndResolveColumns($column->getColumns(), $model)
                );

                if (is_int($alias)) {
                    // Scalar queries and such
                    yield [$model, $alias, $column];

                    continue;
                }

                $columnPath = &$alias;
            } elseif ($column === '*') {
                yield [$model, $alias, $column];

                continue;
            }

            $dot = strrpos($columnPath, '.');

            switch (true) {
                /** @noinspection PhpMissingBreakStatementInspection */
                case $dot !== false:
                    $hydrationPath = substr($columnPath, 0, $dot);
                    $columnPath = substr($columnPath, $dot + 1); // Updates also $column or $alias

                    if ($hydrationPath !== $tableName) {
                        $hydrationPath = $this->qualifyPath($hydrationPath, $tableName);

                        $relations = new AppendIterator();
                        $relations->append(new ArrayIterator([$tableName => null]));
                        $relations->append($this->resolveRelations($hydrationPath));
                        foreach ($relations as $relationPath => $relation) {
                            if ($column instanceof ExpressionInterface) {
                                continue;
                            }

                            if ($relationPath === $tableName) {
                                $subject = $model;
                            } else {
                                /** @var Relation $relation */
                                $subject = $relation->getTarget();
                            }

                            $columnName = $columnPath;
                            if ($relationPath !== $hydrationPath) {
                                // It's still an intermediate relation, not the target
                                $columnName = substr($hydrationPath, strlen($relationPath) + 1) . ".$columnName";
                            }

                            $newColumn = $this->getBehaviors($subject)->rewriteColumn($columnName, $relationPath);
                            if ($newColumn !== null) {
                                if ($newColumn instanceof ExpressionInterface) {
                                    $column = $newColumn;
                                    $target = $subject;
                                    break 2; // Expressions don't need to be *withed* and get no automatic alias either
                                }

                                $column = $newColumn;
                                break;
                            }
                        }

                        if (is_int($alias) && $relationPath !== $hydrationPath) {
                            // If the actual relation is resolved differently,
                            // ensure the hydration path is not an unexpected one
                            $alias = "$hydrationPath.$column";
                        }

                        $this->query->with($hydrationPath);
                        $target = $relation->getTarget();

                        break;
                    }
                // Move to default
                default:
                    $relationPath = null;
                    $target = $model;

                    if (! $column instanceof ExpressionInterface) {
                        $column = $this->getBehaviors($target)->rewriteColumn($column) ?: $column;
                    }

                    if (is_int($alias) && ! $column instanceof AliasedExpression) {
                        if (! isset($baseTableColumns[$columnPath])) {
                            $baseTableColumns[$columnPath] = true;
                        } else {
                            // Don't yield base table columns multiple times.
                            // Duplicate columns without an alias may lead to SQL errors
                            continue 2;
                        }
                    }
            }

            if (! $column instanceof ExpressionInterface) {
                $targetColumns = $target->getColumns();
                if (isset($targetColumns[$column])) {
                    // $column is actually an alias
                    $alias = is_string($alias) ? $alias : ($relationPath ? "$hydrationPath.$column" : $column);
                    $column = $targetColumns[$column];

                    if ($column instanceof ExpressionInterface) {
                        $qualifier = $relationPath ? "$hydrationPath." : '';

                        $column = new ResolvedExpression(
                            $column,
                            $this->requireAndResolveColumns(array_map(function ($c) use ($qualifier) {
                                return $qualifier . $c;
                            }, $column->getColumns()), $model)
                        );
                    }
                }
            }

            if (! $column instanceof ExpressionInterface && ! $this->hasSelectableColumn($target, $columnPath)) {
                throw new InvalidColumnException($columnPath, $target);
            }

            yield [$target, $alias, $column];
        }
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
            if (is_string($alias)) {
                $selectable[$alias] = true;
            }

            if (is_string($column)) {
                $selectable[$column] = true;
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
        $definitions = [];
        foreach ($subject->getColumnDefinitions() as $name => $data) {
            if ($data instanceof ColumnDefinition) {
                $definition = $data;
            } else {
                if (is_string($data)) {
                    $data = ['name' => $name, 'label' => $data];
                } elseif (! isset($data[$name])) {
                    $data['name'] = $name;
                }

                $definition = ColumnDefinition::fromArray($data);
            }

            if (is_string($name) && $definition->getName() !== $name) {
                throw new LogicException(sprintf(
                    'Model %s provides a column definition with a different name (%s) than the index (%s)',
                    get_class($subject),
                    $definition->getName(),
                    $name
                ));
            }

            $definitions[$name] = $definition;
        }

        return $definitions;
    }
}
