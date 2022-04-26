<?php

namespace ipl\Sql\Compat;

use InvalidArgumentException;
use ipl\Sql\Filter\Exists;
use ipl\Sql\Filter\NotExists;
use ipl\Sql\Sql;
use ipl\Stdlib\Filter;

class FilterProcessor
{
    public static function assembleFilter(Filter\Rule $filter, $level = 0)
    {
        $condition = null;

        if ($filter instanceof Filter\Chain) {
            if ($filter instanceof Filter\All) {
                $operator = Sql::ALL;
            } elseif ($filter instanceof Filter\Any) {
                $operator = Sql::ANY;
            } elseif ($filter instanceof Filter\None) {
                $operator = Sql::NOT_ALL;
            }

            if (! isset($operator)) {
                throw new InvalidArgumentException(sprintf('Cannot render filter: %s', get_class($filter)));
            }

            if (! $filter->isEmpty()) {
                foreach ($filter as $filterPart) {
                    $part = static::assembleFilter($filterPart, $level + 1);
                    if ($part) {
                        if ($condition === null) {
                            $condition = [$operator, [$part]];
                        } else {
                            if ($condition[0] === $operator) {
                                $condition[1][] = $part;
                            } elseif ($operator === Sql::NOT_ALL) {
                                $condition = [Sql::ALL, [$condition, [$operator, [$part]]]];
                            } elseif ($operator === Sql::NOT_ANY) {
                                $condition = [Sql::ANY, [$condition, [$operator, [$part]]]];
                            } else {
                                $condition = [$operator, [$condition, $part]];
                            }
                        }
                    }
                }
            } else {
                // TODO(el): Explicitly return the empty string due to the FilterNot case?
            }
        } else {
            /** @var Filter\Condition $filter */
            $condition = [Sql::ALL, static::assemblePredicate($filter)];
        }

        return $condition;
    }

    public static function assemblePredicate(Filter\Condition $filter)
    {
        $column = $filter->getColumn();
        $expression = $filter->getValue();

        if (is_array($expression)) {
            if ($filter instanceof Filter\UnEqual) {
                return ["($column NOT IN (?) OR $column IS NULL)" => $expression];
            } elseif ($filter instanceof Filter\Equal) {
                return ["$column IN (?)" => $expression];
            }

            throw new InvalidArgumentException(
                'Unable to render array expressions with operators other than equal or not equal'
            );
        } elseif (
            ($filter instanceof Filter\Equal || $filter instanceof Filter\Unequal)
            && strpos($expression, '*') !== false
        ) {
            if ($expression === '*') {
                return ["$column IS " . ($filter instanceof Filter\Equal ? 'NOT ' : '') . 'NULL'];
            } elseif ($filter instanceof Filter\Unequal) {
                return ["($column NOT LIKE ? OR $column IS NULL)" => str_replace('*', '%', $expression)];
            } else {
                return ["$column LIKE ?" => str_replace('*', '%', $expression)];
            }
        } elseif ($filter instanceof Filter\Unequal) {
            return ["($column != ? OR $column IS NULL)" => $expression];
        } else {
            if ($filter instanceof Filter\Equal) {
                $operator = '=';
            } elseif ($filter instanceof Filter\GreaterThan) {
                $operator = '>';
            } elseif ($filter instanceof Filter\GreaterThanOrEqual) {
                $operator = '>=';
            } elseif ($filter instanceof Filter\LessThan) {
                $operator = '<';
            } elseif ($filter instanceof Filter\LessThanOrEqual) {
                $operator = '<=';
            } elseif ($filter instanceof Exists) {
                $operator = 'EXISTS';
            } elseif ($filter instanceof NotExists) {
                $operator = 'NOT EXISTS';
            } else {
                throw new InvalidArgumentException(sprintf('Cannot render filter: %s', get_class($filter)));
            }

            return ["$column $operator ?" => $expression];
        }
    }
}
