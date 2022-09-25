<?php

namespace ipl\Orm;

use ArrayObject;
use Generator;
use InvalidArgumentException;
use ipl\Orm\Common\SortUtil;
use ipl\Orm\Compat\FilterProcessor;
use ipl\Sql\Connection;
use ipl\Sql\ExpressionInterface;
use ipl\Sql\LimitOffset;
use ipl\Sql\LimitOffsetInterface;
use ipl\Sql\OrderBy;
use ipl\Sql\OrderByInterface;
use ipl\Sql\Select;
use ipl\Stdlib\Contract\Filterable;
use ipl\Stdlib\Contract\Paginatable;
use ipl\Stdlib\Events;
use ipl\Stdlib\Filter;
use ipl\Stdlib\Filters;
use IteratorAggregate;
use ReflectionClass;
use SplObjectStorage;
use Traversable;

/**
 * Represents a database query which is associated to a model and a database connection.
 */
class Query implements Filterable, LimitOffsetInterface, OrderByInterface, Paginatable, IteratorAggregate
{
    use Events;
    use Filters;
    use LimitOffset;
    use OrderBy;

    /**
     * Event raised after assembling a {@link Select} object for the query
     *
     * **Example usage:**
     *
     * ```
     * $query->on(Query::ON_SELECT_ASSEMBLED, function (Select $select) {
     *     // ...
     * });
     * ```
     */
    const ON_SELECT_ASSEMBLED = 'selectAssembled';

    /** @var int Count cache */
    protected $count;

    /** @var Connection Database connection */
    protected $db;

    /** @var string Class to return results as */
    protected $resultSetClass = ResultSet::class;

    /** @var Model Model to query */
    protected $model;

    /** @var array Columns to select from the model (or its relations). If empty, all columns are selected */
    protected $columns = [];

    /** @var array Additional columns to select from the model (or its relations) */
    protected $withColumns = [];

    /** @var bool Whether to peek ahead for more results */
    protected $peekAhead = false;

    /** @var Resolver Column and relation resolver */
    protected $resolver;

    /** @var Select Base SELECT query */
    protected $selectBase;

    /** @var Relation[] Relations to eager load */
    protected $with = [];

    /** @var Relation[] Relations to utilize (join) */
    protected $utilize = [];

    /** @var bool Whether to disable the default sorts of the model */
    protected $disableDefaultSort = false;

    /**
     * Get the database connection
     *
     * @return Connection
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * Set the database connection
     *
     * @param Connection $db
     *
     * @return $this
     */
    public function setDb(Connection $db)
    {
        $this->db = $db;

        return $this;
    }

    /**
     * Get the class to return results as
     *
     * @return string
     */
    public function getResultSetClass()
    {
        return $this->resultSetClass;
    }

    /**
     * Set the class to return results as
     *
     * @param string $class
     *
     * @return $this
     *
     * @throws InvalidArgumentException If class is not an instance of {@link ResultSet}
     */
    public function setResultSetClass($class)
    {
        if (! is_string($class)) {
            throw new InvalidArgumentException('Argument $class must be a string');
        }

        if (! (new ReflectionClass($class))->newInstanceWithoutConstructor() instanceof ResultSet) {
            throw new InvalidArgumentException(
                $class . ' must be an instance of ' . ResultSet::class
            );
        }

        $this->resultSetClass = $class;

        return $this;
    }

    /**
     * Get the model to query
     *
     * @return Model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Set the model to query
     *
     * @param $model
     *
     * @return $this
     */
    public function setModel(Model $model)
    {
        $this->model = $model;
        $this->getResolver()->setAlias($model, $model->getTableName());

        return $this;
    }

    /**
     * Get the columns to select from the model
     *
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Set the filter of the query
     *
     * @param Filter\Chain $filter
     *
     * @return $this
     */
    public function setFilter(Filter\Chain $filter)
    {
        $this->filter = $filter;

        return $this;
    }

    /**
     * Disable default sorts
     *
     * Prevents the default sort rules of the source model from being used
     *
     * @param bool $disable
     *
     * @return $this
     */
    public function disableDefaultSort($disable = true)
    {
        $this->disableDefaultSort = (bool) $disable;

        return $this;
    }

    /**
     * Get whether to not use the default sort rules of the source model
     *
     * @return bool
     */
    public function defaultSortDisabled()
    {
        return $this->disableDefaultSort;
    }


    /**
     * Set columns to select from the model (or its relations)
     *
     * By default, i.e. if you do not specify any columns, all columns of the model and
     * any relation added via {@see with()} will be selected.
     * Multiple calls to this method will overwrite the previously specified columns.
     * If you specify columns from the model's relations, the relations are automatically joined upon querying.
     * Note that a call to this method also overwrites any previously column specified via {@see withColumns()}.
     *
     * @param string|array $columns The column(s) to select
     *
     * @return $this
     */
    public function columns($columns)
    {
        $this->columns = (array) $columns;
        $this->withColumns = [];

        return $this;
    }

    /**
     * Set additional columns to select from the model (or its relations)
     *
     * Multiple calls to this method will not overwrite the previous set columns but append the columns to the query.
     *
     * @param string|array $columns The column(s) to select
     *
     * @return $this
     */
    public function withColumns($columns)
    {
        $this->withColumns = array_merge($this->withColumns, (array) $columns);

        return $this;
    }

    /**
     * Get the query's resolver
     *
     * @return Resolver
     */
    public function getResolver()
    {
        if ($this->resolver === null) {
            $this->resolver = new Resolver($this);
        }

        return $this->resolver;
    }

    /**
     * Get the SELECT base query
     *
     * @return Select
     */
    public function getSelectBase()
    {
        if ($this->selectBase === null) {
            $this->selectBase = new Select();

            $this->selectBase->from([
                $this->getResolver()->getAlias($this->getModel()) => $this->getModel()->getTableName()
            ]);
        }

        return $this->selectBase;
    }

    /**
     * Get the relations to eager load
     *
     * @return Relation[]
     */
    public function getWith()
    {
        return $this->with;
    }

    /**
     * Add a relation to eager load
     *
     * @param string|array $relations
     *
     * @return $this
     */
    public function with($relations)
    {
        $tableName = $this->getModel()->getTableName();
        foreach ((array) $relations as $relation) {
            $relation = $this->getResolver()->qualifyPath($relation, $tableName);
            $this->with[$relation] = $this->getResolver()->resolveRelation($relation);
        }

        return $this;
    }

    /**
     * Remove an eager loaded relation
     *
     * @param string|array $relations
     *
     * @return $this
     */
    public function without($relations)
    {
        $tableName = $this->getModel()->getTableName();
        foreach ((array) $relations as $relation) {
            $relation = $this->getResolver()->qualifyPath($relation, $tableName);
            unset($this->with[$relation]);
        }

        return $this;
    }

    /**
     * Get utilized (joined) relations
     *
     * @return Relation[]
     */
    public function getUtilize()
    {
        return $this->utilize;
    }

    /**
     * Add a relation to utilize (join)
     *
     * @param string $path
     *
     * @return $this
     */
    public function utilize($path)
    {
        $path = $this->getResolver()->qualifyPath($path, $this->getModel()->getTableName());
        $this->utilize[$path] = $this->getResolver()->resolveRelation($path);

        return $this;
    }

    /**
     * Remove a utilized (joined) relation
     *
     * @param string $path
     *
     * @return $this
     */
    public function omit($path)
    {
        $path = $this->getResolver()->qualifyPath($path, $this->getModel()->getTableName());
        unset($this->utilize[$path]);

        return $this;
    }

    /**
     * Assemble and return the SELECT query
     *
     * @return Select
     */
    public function assembleSelect()
    {
        $columns = $this->getColumns();
        $model = $this->getModel();
        $resolver = $this->getResolver();
        $select = clone $this->getSelectBase();

        if (empty($columns)) {
            $columns = $resolver->getSelectColumns($model);

            foreach ($this->getWith() as $path => $relation) {
                foreach ($resolver->getSelectColumns($relation->getTarget()) as $alias => $column) {
                    if (is_int($alias)) {
                        $columns[] = "$path.$column";
                    } else {
                        $columns[] = "$path.$alias";
                    }
                }
            }

            $columns = array_merge($columns, $this->withColumns);
            $customAliases = array_flip(array_filter(array_keys($this->withColumns), 'is_string'));
        } else {
            $columns = array_merge($columns, $this->withColumns);
            $customAliases = array_flip(array_filter(array_keys($columns), 'is_string'));
        }

        $resolved = $this->groupColumnsByTarget($resolver->requireAndResolveColumns($columns));
        foreach ($resolved as $target) {
            $targetColumns = $resolved[$target]->getArrayCopy();
            if (! empty($customAliases)) {
                $customColumns = array_intersect_key($targetColumns, $customAliases);
                $targetColumns = array_diff_key($targetColumns, $customAliases);

                $select->columns($resolver->qualifyColumns($customColumns, $target));
            }

            $select->columns(
                $resolver->qualifyColumnsAndAliases(
                    $targetColumns,
                    $target,
                    $target !== $model
                )
            );
        }

        $filter = clone $this->getFilter();
        FilterProcessor::resolveFilter($filter, $this);
        $where = FilterProcessor::assembleFilter($filter);
        if ($where) {
            $select->where(...array_reverse($where));
        }

        $joinedRelations = [];
        foreach ($this->getWith() + $this->getUtilize() as $path => $_) {
            foreach ($resolver->resolveRelations($path) as $relationPath => $relation) {
                if (isset($joinedRelations[$relationPath])) {
                    continue;
                }

                foreach ($relation->resolve() as list($source, $target, $relatedKeys)) {
                    /** @var Model $source */
                    /** @var Model $target */

                    $sourceAlias = $resolver->getAlias($source);
                    $targetAlias = $resolver->getAlias($target);

                    $conditions = [];
                    foreach ($relatedKeys as $fk => $ck) {
                        $conditions[] = sprintf(
                            '%s = %s',
                            $resolver->qualifyColumn($fk, $targetAlias),
                            $resolver->qualifyColumn($ck, $sourceAlias)
                        );
                    }

                    $table = [$targetAlias => $target->getTableName()];

                    switch ($relation->getJoinType()) {
                        case 'LEFT':
                            $select->joinLeft($table, $conditions);

                            break;
                        case 'RIGHT':
                            $select->joinRight($table, $conditions);

                            break;
                        case 'INNER':
                        default:
                            $select->join($table, $conditions);
                    }
                }

                $joinedRelations[$relationPath] = true;
            }
        }

        if ($this->hasLimit()) {
            $limit = $this->getLimit();

            if ($this->peekAhead) {
                ++$limit;
            }

            $select->limit($limit);
        }
        if ($this->hasOffset()) {
            $select->offset($this->getOffset());
        }

        $this->order($select);

        $this->emit(static::ON_SELECT_ASSEMBLED, [$select]);

        return $select;
    }

    /**
     * Create and return the hydrator
     *
     * @return Hydrator
     */
    public function createHydrator()
    {
        $hydrator = new Hydrator($this);

        $hydrator->add($this->getModel()->getTableName());
        foreach ($this->getWith() as $path => $_) {
            $hydrator->add($path);
        }

        return $hydrator;
    }

    /**
     * Derive a new query to load the specified relation from a concrete model
     *
     * @param string $relation
     * @param Model  $source
     *
     * @return static
     *
     * @throws InvalidArgumentException If the relation with the given name does not exist
     */
    public function derive($relation, Model $source)
    {
        // TODO: Think of a way to merge derive() and createSubQuery()
        return $this->createSubQuery(
            $this->getResolver()->getRelations($source)->get($relation)->getTarget(),
            $this->getResolver()->qualifyPath($relation, $source->getTableName()),
            $source
        );
    }

    /**
     * Create a sub-query linked to rows of this query
     *
     * @param Model $target The model to query
     * @param string $targetPath The target's absolute relation path
     * @param Model $from The source model
     *
     * @return static
     */
    public function createSubQuery(Model $target, $targetPath, Model $from = null)
    {
        $subQuery = (new static())
            ->setDb($this->getDb())
            ->setModel($target);

        $resolver = $this->getResolver();
        $sourceParts = array_reverse(explode('.', $targetPath));
        $sourceParts[0] = $target->getTableName();

        $subQueryResolver = $subQuery->getResolver();
        $sourcePath = join('.', $sourceParts);
        $subQuery->utilize($sourcePath); // TODO: Don't join if there's a matching foreign key

        // TODO: Should be done by the caller. Though, that's not possible until we've got a filter abstraction
        //       which allows to post-pone filter column qualification.
        $subQueryResolver->setAliasPrefix('sub_');

        $baseAlias = $resolver->getAlias($this->getModel());
        $sourceAlias = $subQueryResolver->getAlias($subQueryResolver->resolveRelation($sourcePath)->getTarget());

        $subQueryConditions = [];
        foreach ((array) $this->getModel()->getKeyName() as $column) {
            $fk = $subQueryResolver->qualifyColumn($column, $sourceAlias);

            if (isset($from->$column)) {
                $subQueryConditions["$fk = ?"] = $resolver
                    ->getBehaviors($from)
                    ->persistProperty($from->$column, $column);
            } else {
                $subQueryConditions[] = "$fk = " . $resolver->qualifyColumn($column, $baseAlias);
            }
        }

        $subQuery->getSelectBase()->where($subQueryConditions);

        return $subQuery;
    }

    /**
     * Dump the query
     *
     * @return array
     */
    public function dump()
    {
        return $this->getDb()->getQueryBuilder()->assembleSelect($this->assembleSelect());
    }

    /**
     * Execute the query
     *
     * @return ResultSet
     */
    public function execute()
    {
        $class = $this->getResultSetClass();
        /** @var ResultSet $class Just for type hinting. $class is of course a string */

        return $class::fromQuery($this);
    }

    /**
     * Fetch and return the first result
     *
     * @return Model|null Null in case there's no result
     */
    public function first()
    {
        return $this->execute()->current();
    }

    /**
     * Set whether to peek ahead for more results
     *
     * Enabling this causes the current query limit to be increased by one. The potential extra row being yielded will
     * be removed from the result set. Note that this only applies when fetching multiple results of limited queries.
     *
     * @param bool $peekAhead
     *
     * @return $this
     */
    public function peekAhead($peekAhead = true)
    {
        $this->peekAhead = (bool) $peekAhead;

        return $this;
    }

    /**
     * Yield the query's results
     *
     * @return \Generator
     */
    public function yieldResults()
    {
        $select = $this->assembleSelect();
        $stmt = $this->getDb()->select($select);
        $stmt->setFetchMode(\PDO::FETCH_ASSOC);

        $hydrator = $this->createHydrator();
        $modelClass = get_class($this->getModel());

        foreach ($stmt as $row) {
            yield $hydrator->hydrate($row, new $modelClass());
        }
    }

    public function count(): int
    {
        if ($this->count === null) {
            $this->count = $this->getDb()->select($this->assembleSelect()->getCountQuery())->fetchColumn(0);
        }

        return $this->count;
    }

    public function getIterator(): Traversable
    {
        return $this->execute();
    }

    /**
     * Group columns from {@link Resolver::requireAndResolveColumns()} by target models
     *
     * @param Generator $columns
     *
     * @return SplObjectStorage
     */
    protected function groupColumnsByTarget(Generator $columns)
    {
        $columnStorage = new SplObjectStorage();

        foreach ($columns as list($target, $alias, $column)) {
            if (! $columnStorage->contains($target)) {
                $resolved = new ArrayObject();
                $columnStorage->attach($target, $resolved);
            } else {
                $resolved = $columnStorage[$target];
            }

            if (is_int($alias)) {
                $resolved[] = $column;
            } else {
                $resolved[$alias] = $column;
            }
        }

        return $columnStorage;
    }

    /**
     * Resolve, require and apply ORDER BY columns
     *
     * @param Select $select
     *
     * @return $this
     */
    protected function order(Select $select)
    {
        $orderBy = $this->getOrderBy();
        $defaultSort = [];
        if (! $this->defaultSortDisabled()) {
            $defaultSort = (array) $this->getModel()->getDefaultSort();
        }

        if (empty($orderBy)) {
            if (empty($defaultSort)) {
                return $this;
            }

            $orderBy = SortUtil::createOrderBy($defaultSort);
        }

        $columnsAndDirections = [];
        $orderByResolved = [];
        $resolver = $this->getResolver();
        $selectedColumns = $select->getColumns();

        foreach ($orderBy as $part) {
            list($column, $direction) = $part;

            if (isset($selectedColumns[$column])) {
                // If it's a custom alias, we have no other way of knowing it,
                // unless the caller explicitly uses it in the sort rule.
                $orderByResolved[] = $part;
            } else {
                // Prepare flat ORDER BY column(s) and direction(s) for requireAndResolveColumns()
                $columnsAndDirections[$column] = $direction;
            }
        }

        foreach (
            $resolver->requireAndResolveColumns(array_keys($columnsAndDirections)) as list($model, $alias, $column)
        ) {
            $direction = reset($columnsAndDirections);
            $selectColumns = $resolver->getSelectColumns($model);
            $tableName = $resolver->getAlias($model);

            if ($column instanceof ExpressionInterface) {
                if (is_int($alias) && $column instanceof AliasedExpression) {
                    $alias = $column->getAlias();
                } elseif (is_string($alias) && $model !== $this->getModel()) {
                    $alias = $resolver->qualifyColumnAlias($alias, $tableName);
                }

                if (is_string($alias) && isset($selectedColumns[$alias])) {
                    // An expression's alias can only be used if the expression is also selected
                    $column = $alias;
                }
            } else {
                if (isset($selectColumns[$column])) {
                    $column = $selectColumns[$column];
                }

                if (is_string($column)) {
                    $column = $resolver->qualifyColumn($column, $tableName);
                }
            }

            $orderByResolved[] = [$column, $direction];

            array_shift($columnsAndDirections);
        }

        $select->orderBy($orderByResolved);

        return $this;
    }

    public function __clone()
    {
        $this->resolver = clone $this->resolver;

        if ($this->filter !== null) {
            $this->filter = clone $this->filter;
        }

        if ($this->selectBase !== null) {
            $this->selectBase = clone $this->selectBase;
        }
    }
}
