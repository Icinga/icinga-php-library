<?php

namespace ipl\Orm\Compat;

use AppendIterator;
use ArrayIterator;
use ipl\Orm\Exception\InvalidColumnException;
use ipl\Orm\Exception\ValueConversionException;
use ipl\Orm\Query;
use ipl\Orm\Relation;
use ipl\Orm\UnionQuery;
use ipl\Sql\Expression;
use ipl\Sql\Filter\Exists;
use ipl\Sql\Filter\NotExists;
use ipl\Stdlib\Contract\Filterable;
use ipl\Stdlib\Filter\MetaDataProvider;
use ipl\Stdlib\Filter;

class FilterProcessor extends \ipl\Sql\Compat\FilterProcessor
{
    protected $baseJoins = [];

    protected $madeJoins = [];

    /**
     * Require and resolve the filter rule and apply it on the query
     *
     * Note that this applies the filter to {@see Query::$selectBase}
     * directly and bypasses {@see Query::$filter}. If this is not
     * desired, utilize the {@see Filterable} functions of the query.
     *
     * @param Filter\Rule $filter
     * @param Query $query
     */
    public static function apply(Filter\Rule $filter, Query $query)
    {
        if ($query instanceof UnionQuery) {
            foreach ($query->getUnions() as $union) {
                static::apply($filter, $union);
            }

            return;
        }

        if ($filter instanceof Filter\Condition || ! $filter->isEmpty()) {
            $filter = clone $filter;
            if (! $filter instanceof Filter\Chain) {
                $filter = Filter::all($filter);
            }

            static::resolveFilter($filter, $query);

            $where = static::assembleFilter($filter);

            if ($where) {
                $operator = array_shift($where);
                $conditions = array_shift($where);

                $query->getSelectBase()->where($conditions, $operator);
            }
        }
    }

    /**
     * Resolve the filter in order to apply it on the query
     *
     * @param Filter\Chain $filter
     * @param Query $query
     *
     * @return void
     */
    public static function resolveFilter(Filter\Chain $filter, Query $query)
    {
        $processor = new static();
        foreach ($query->getUtilize() as $path => $_) {
            $processor->baseJoins[$path] = true;
        }

        $processor->requireAndResolveFilterColumns($filter, $query);
    }

    protected function requireAndResolveFilterColumns(Filter\Rule $filter, Query $query, $forceOptimization = null)
    {
        if ($filter instanceof Filter\Condition) {
            if ($filter instanceof Exists || $filter instanceof NotExists) {
                return;
            }

            $resolver = $query->getResolver();
            $baseTable = $query->getModel()->getTableAlias();
            $column = $resolver->qualifyPath(
                $filter->metaData()->get('columnName', $filter->getColumn()),
                $baseTable
            );

            $filter->metaData()->set('columnPath', $column);

            list($relationPath, $columnName) = preg_split('/\.(?=[^.]+$)/', $column);

            $relations = new AppendIterator();
            $relations->append(new ArrayIterator([$baseTable => null]));
            $relations->append($resolver->resolveRelations($relationPath));
            foreach ($relations as $path => $relation) {
                $columnName = substr($column, strlen($path) + 1);

                if ($path === $baseTable) {
                    $subject = $query->getModel();
                } else {
                    /** @var Relation $relation */
                    $subject = $relation->getTarget();
                }

                $subjectBehaviors = $resolver->getBehaviors($subject);
                // This is only used within the Binary behavior in rewriteCondition().
                $filter->metaData()->set('originalValue', $filter->getValue());

                try {
                    // Prepare filter as if it were final to allow full control for rewrite filter behaviors
                    $filter->setValue($subjectBehaviors->persistProperty($filter->getValue(), $columnName));
                } catch (ValueConversionException $_) {
                    // The search bar may submit values with wildcards or whatever the user has entered.
                    // In this case, we can simply ignore this error instead of rendering a stack trace.
                }

                $filter->setColumn($resolver->getAlias($subject) . '.' . $columnName);
                $filter->metaData()->set('columnName', $columnName);
                $filter->metaData()->set('relationPath', $path);

                $rewrittenFilter = $subjectBehaviors->rewriteCondition($filter, $path . '.');
                if ($rewrittenFilter !== null) {
                    return $this->requireAndResolveFilterColumns($rewrittenFilter, $query, $forceOptimization)
                        ?: $rewrittenFilter;
                }
            }

            if (! $resolver->hasSelectableColumn($subject, $columnName)) {
                throw new InvalidColumnException($columnName, $subject);
            }

            if ($relationPath !== $baseTable) {
                $query->utilize($relationPath);
                $this->madeJoins[$relationPath][] = $filter;
            }
        } else {
            /** @var Filter\Chain $filter */

            if ($filter->metaData()->has('forceOptimization')) {
                // Rules can override the default behavior how it's determined that they need to be
                // optimized. If it's done by a chain, it applies to all of its children.
                $forceOptimization = $filter->metaData()->get('forceOptimization');
            }

            $subQueryGroups = [];
            $outsourcedRules = [];
            foreach ($filter as $child) {
                /** @var Filter\Rule $child */
                $rewrittenFilter = $this->requireAndResolveFilterColumns($child, $query, $forceOptimization);
                if ($rewrittenFilter !== null) {
                    $filter->replace($child, $rewrittenFilter);
                    $child = $rewrittenFilter;
                }

                $optimizeChild = $forceOptimization;
                if ($child instanceof MetaDataProvider && $child->metaData()->has('forceOptimization')) {
                    $optimizeChild = $child->metaData()->get('forceOptimization');
                }

                // We only optimize rules in a single level, nested chains are ignored
                if ($child instanceof Filter\Condition && $child->metaData()->has('relationPath')) {
                    $relationPath = $child->metaData()->get('relationPath');
                    if (
                        $relationPath !== $query->getModel()->getTableAlias() // Not the base table
                        && (
                            $optimizeChild !== null && $optimizeChild
                            || (
                                $optimizeChild === null
                                && ! isset($query->getWith()[$relationPath]) // Not a selected join
                                && ! $query->getResolver()->isDistinctRelation($relationPath) // Not a to-one relation
                            )
                        )
                    ) {
                        $subQueryGroups[$relationPath][$child->getColumn()][get_class($child)][] = $child;

                        // Register all rules that are going to be put into sub queries, for later cleanup
                        $outsourcedRules[] = $child;
                    }
                }
            }

            foreach ($subQueryGroups as $relationPath => $columns) {
                $generalRules = [];
                foreach ($columns as $column => & $comparisons) {
                    if (isset($comparisons[Filter\Unequal::class]) || isset($comparisons[Filter\Unlike::class])) {
                        // If there's a unequal (!=) comparison for any column, all other comparisons (for the same
                        // column) also need to be outsourced to their own sub query. Regardless of their amount of
                        // occurrence. This is because `$generalRules` apply to all comparisons of such a column and
                        // need to be applied to all sub queries.
                        continue;
                    }

                    // Single occurring columns don't need their own sub query
                    foreach ($comparisons as $conditionClass => $rules) {
                        if (count($rules) === 1) {
                            $generalRules[] = $rules[0];
                            unset($comparisons[$conditionClass]);
                        }
                    }

                    if (empty($comparisons)) {
                        unset($columns[$column]);
                    }
                }

                $count = null;
                $baseFilters = null;
                $subQueryFilters = [];
                foreach ($columns as $column => $comparisons) {
                    foreach ($comparisons as $conditionClass => $rules) {
                        if ($conditionClass === Filter\Unequal::class || $conditionClass === Filter\Unlike::class) {
                            // Unequal comparisons are always put into their own sub query
                            $subQueryFilters[] = [$rules, count($rules), true];
                        } elseif (count($rules) > $count) {
                            // If there are multiple columns used multiple times in the same relation, we have to decide
                            // which to use as the primary comparison. That is the column that is used most often.
                            if (! empty($baseFilters)) {
                                array_push($generalRules, ...$baseFilters);
                            }

                            $count = count($rules);
                            $baseFilters = $rules;
                        } else {
                            array_push($generalRules, ...$rules);
                        }
                    }
                }

                if (! empty($baseFilters) || ! empty($generalRules)) {
                    $subQueryFilters[] = [$baseFilters ?: $generalRules, $count, false];
                }

                foreach ($subQueryFilters as list($filters, $count, $negate)) {
                    $subQueryFilter = null;
                    if ($count !== null) {
                        $aggregateFilter = Filter::any();
                        foreach ($filters as $condition) {
                            if ($negate) {
                                if ($condition instanceof Filter\Unequal) {
                                    $negation = Filter::equal($condition->getColumn(), $condition->getValue());
                                } else { // if ($condition instanceof Filter\Unlike)
                                    $negation = Filter::like($condition->getColumn(), $condition->getValue());
                                }

                                $negation->metaData()->merge($condition->metaData());
                                $condition = $negation;
                                $count = 1;
                            }

                            switch (true) {
                                case $filter instanceof Filter\All:
                                    $aggregateFilter->add(Filter::all($condition, ...$generalRules));
                                    break;
                                case $filter instanceof Filter\Any:
                                    $aggregateFilter->add(Filter::any($condition, ...$generalRules));
                                    break;
                                case $filter instanceof Filter\None:
                                    $aggregateFilter->add(Filter::none($condition, ...$generalRules));
                                    break;
                            }
                        }

                        $subQueryFilter = $aggregateFilter;
                    } else {
                        switch (true) {
                            case $filter instanceof Filter\All:
                                $subQueryFilter = Filter::all(...$filters);
                                break;
                            case $filter instanceof Filter\Any:
                                $subQueryFilter = Filter::any(...$filters);
                                break;
                            case $filter instanceof Filter\None:
                                $subQueryFilter = Filter::none(...$filters);
                                break;
                        }
                    }

                    $relation = $query->getResolver()->resolveRelation($relationPath);
                    $subQuery = $query->createSubQuery($relation->getTarget(), $relationPath);
                    $subQuery->columns([new Expression('1')]);

                    if ($count !== null && ($negate || $filter instanceof Filter\All)) {
                        $targetKeys = join(
                            ',',
                            array_values(
                                $subQuery->getResolver()->qualifyColumns(
                                    (array) $subQuery->getModel()->getKeyName(),
                                    $subQuery->getModel()
                                )
                            )
                        );

                        $subQuery->getSelectBase()->having(["COUNT(DISTINCT $targetKeys) >= ?" => $count]);
                    }

                    $subQuery->filter($subQueryFilter);

                    if ($negate) {
                        $filter->add(new NotExists($subQuery->assembleSelect()->resetOrderBy()));
                    } else {
                        $filter->add(new Exists($subQuery->assembleSelect()->resetOrderBy()));
                    }
                }
            }

            foreach ($outsourcedRules as $rule) {
                // Remove joins solely used for filter conditions
                foreach ($this->madeJoins as $joinPath => & $madeBy) {
                    $madeBy = array_filter(
                        $madeBy,
                        function ($relationFilter) use ($rule) {
                            return $rule !== $relationFilter
                                && ($rule instanceof Filter\Condition || ! $rule->has($relationFilter));
                        }
                    );

                    if (empty($madeBy)) {
                        if (! isset($this->baseJoins[$joinPath])) {
                            $query->omit($joinPath);
                        }

                        unset($this->madeJoins[$joinPath]);
                    }
                }

                $filter->remove($rule);
            }
        }
    }
}
