<?php

namespace ipl\Stdlib;

use ipl\Stdlib\Filter\All;
use ipl\Stdlib\Filter\Any;
use ipl\Stdlib\Filter\Chain;
use ipl\Stdlib\Filter\Condition;
use ipl\Stdlib\Filter\Equal;
use ipl\Stdlib\Filter\GreaterThan;
use ipl\Stdlib\Filter\GreaterThanOrEqual;
use ipl\Stdlib\Filter\LessThan;
use ipl\Stdlib\Filter\LessThanOrEqual;
use ipl\Stdlib\Filter\Like;
use ipl\Stdlib\Filter\None;
use ipl\Stdlib\Filter\Rule;
use ipl\Stdlib\Filter\Unequal;
use ipl\Stdlib\Filter\Unlike;

/**
 * Build filter rules and evaluate them against rows
 */
class Filter
{
    /**
     * Create a new Filter
     *
     * Intentionally protected; use the static factory methods instead.
     */
    protected function __construct()
    {
    }

    /**
     * Check whether the given rule matches the given item
     *
     * @param Rule $rule
     * @param array|object $row
     *
     * @return bool
     */
    public static function match(Rule $rule, array|object $row): bool
    {
        if (! is_object($row)) {
            $row = (object) $row;
        }

        return (new self())->performMatch($rule, $row);
    }

    /**
     * Create a rule that matches if **all** of the given rules do
     *
     * If no rules are given, the resulting rule always matches.
     *
     * @param Rule ...$rules
     *
     * @return Chain
     */
    public static function all(Rule ...$rules): Chain
    {
        return new All(...$rules);
    }

    /**
     * Return whether the given rules all match the given item
     *
     * @param All $rules
     * @param object $row
     *
     * @return bool True if all rules match; always true if no rules are given
     */
    protected function matchAll(All $rules, object $row): bool
    {
        foreach ($rules as $rule) {
            if (! $this->performMatch($rule, $row)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Create a rule that matches if **any** of the given rules do
     *
     * If no rules are given, the resulting rule never matches.
     *
     * @param Rule ...$rules
     *
     * @return Chain
     */
    public static function any(Rule ...$rules): Chain
    {
        return new Any(...$rules);
    }

    /**
     * Return whether any of the given rules match the given item
     *
     * @param Any $rules
     * @param object $row
     *
     * @return bool True if any rule matches; always false if no rules are given
     */
    protected function matchAny(Any $rules, object $row): bool
    {
        foreach ($rules as $rule) {
            if ($this->performMatch($rule, $row)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create a rule that matches if **none** of the given rules do
     *
     * If no rules are given, the resulting rule always matches.
     *
     * @param Rule ...$rules
     *
     * @return Chain
     */
    public static function none(Rule ...$rules): Chain
    {
        return new None(...$rules);
    }

    /**
     * Return whether none of the given rules match the given item
     *
     * @param None $rules
     * @param object $row
     *
     * @return bool True if no rules match; always true if no rules are given
     */
    protected function matchNone(None $rules, object $row): bool
    {
        foreach ($rules as $rule) {
            if ($this->performMatch($rule, $row)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Create a rule that matches rows with a column that **equals** the given value
     *
     * @param string $column
     * @param mixed $value
     *
     * @return Condition
     */
    public static function equal(string $column, mixed $value): Condition
    {
        return new Equal($column, $value);
    }

    /**
     * Return whether the given rule's value equals the given item's value
     *
     * @param Equal|Unequal $rule
     * @param object $row
     *
     * @return bool
     */
    protected function matchEqual(Equal|Unequal $rule, object $row): bool
    {
        $rowValue = $this->extractValue($rule->getColumn(), $row);
        $value = $rule->getValue();
        $this->normalizeTypes($rowValue, $value);

        if (! is_array($rowValue)) {
            $rowValue = [$rowValue];
        }

        foreach ($rowValue as $rowVal) {
            if ($this->performEqualityMatch($value, $rowVal, $rule->ignoresCase())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create a rule that matches rows with a column that is **similar** to the given value
     *
     * Performs a wildcard search if the value contains asterisks.
     *
     * @param string $column
     * @param mixed $value
     *
     * @return Condition
     */
    public static function like(string $column, mixed $value): Condition
    {
        return new Like($column, $value);
    }

    /**
     * Return whether the given rule's value is similar to the given item's value
     *
     * @param Like|Unlike $rule
     * @param object $row
     *
     * @return bool
     */
    protected function matchSimilar(Like|Unlike $rule, object $row): bool
    {
        $rowValue = $this->extractValue($rule->getColumn(), $row);
        $value = $rule->getValue();
        $this->normalizeTypes($rowValue, $value);

        if (! is_array($rowValue)) {
            $rowValue = [$rowValue];
        }

        foreach ($rowValue as $rowVal) {
            if ($this->performSimilarityMatch($value, $rowVal, $rule->ignoresCase())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Apply equality matching rules on the given row value
     *
     * @param mixed $value
     * @param mixed $rowValue
     * @param bool $ignoreCase
     *
     * @return bool
     */
    protected function performEqualityMatch(mixed $value, mixed $rowValue, bool $ignoreCase = false): bool
    {
        if ($ignoreCase && is_string($rowValue)) {
            $rowValue = strtolower($rowValue);
            /** @var string|string[] $value {@see self::normalizeTypes} ensures this is the case */
            $value = is_array($value)
                ? array_map('strtolower', $value)
                : ($value === null ? null : strtolower($value)); // PHPStan incorrectly infers the type here.
        }

        if (is_array($value)) {
            return in_array($rowValue, $value, true);
        }

        return $rowValue === $value;
    }

    /**
     * Apply similarity matching rules on the given row value
     *
     * @param mixed $value
     * @param mixed $rowValue
     * @param bool $ignoreCase
     *
     * @return bool
     */
    protected function performSimilarityMatch(mixed $value, mixed $rowValue, bool $ignoreCase = false): bool
    {
        if ($ignoreCase && is_string($rowValue)) {
            $rowValue = strtolower($rowValue);
            /** @var string|string[] $value {@see self::normalizeTypes} ensures this is the case */
            $value = is_array($value)
                ? array_map('strtolower', $value)
                : ($value === null ? null : strtolower($value)); // PHPStan incorrectly infers the type here.
        }

        if (is_array($value)) {
            return in_array($rowValue, $value, true);
        } elseif (! is_string($value) || ! is_string($rowValue)) {
            return $this->performEqualityMatch($value, $rowValue);
        }

        $wildcardSubSegments = preg_split('~\*~', $value);
        if (! $wildcardSubSegments) {
            $wildcardSubSegments = [];
        }

        if (count($wildcardSubSegments) === 1) {
            return $rowValue === $value;
        }

        $parts = [];
        foreach ($wildcardSubSegments as $part) {
            $parts[] = preg_quote($part, '~');
        }

        $pattern = '~^' . join('.*', $parts) . '$~';

        return (bool) preg_match($pattern, $rowValue);
    }

    /**
     * Create a rule that matches rows with a column that is **unequal** with the given value
     *
     * @param string $column
     * @param mixed $value
     *
     * @return Condition
     */
    public static function unequal(string $column, mixed $value): Condition
    {
        return new Unequal($column, $value);
    }

    /**
     * Return whether the given rule's value does not equal the given item's value
     *
     * @param Unequal $rule
     * @param object $row
     *
     * @return bool
     */
    protected function matchUnequal(Unequal $rule, object $row): bool
    {
        return ! $this->matchEqual($rule, $row);
    }

    /**
     * Create a rule that matches rows with a column that is **unlike** with the given value
     *
     * Performs a wildcard search if the value contains asterisks.
     *
     * @param string $column
     * @param mixed $value
     *
     * @return Condition
     */
    public static function unlike(string $column, mixed $value): Condition
    {
        return new Unlike($column, $value);
    }

    /**
     * Return whether the given rule's value is unlike the given item's value
     *
     * @param Unlike $rule
     * @param object $row
     *
     * @return bool
     */
    protected function matchUnlike(Unlike $rule, object $row): bool
    {
        return ! $this->matchSimilar($rule, $row);
    }

    /**
     * Create a rule that matches rows with a column that is **greater** than the given value
     *
     * @param string $column
     * @param mixed $value
     *
     * @return Condition
     */
    public static function greaterThan(string $column, mixed $value): Condition
    {
        return new GreaterThan($column, $value);
    }

    /**
     * Return whether the given rule's value is greater than the given item's value
     *
     * @param GreaterThan $rule
     * @param object $row
     *
     * @return bool
     */
    protected function matchGreaterThan(GreaterThan $rule, object $row): bool
    {
        $rowValue = $this->extractValue($rule->getColumn(), $row);
        $value = $rule->getValue();

        return $rowValue !== null && $value !== null && $rowValue > $value;
    }

    /**
     * Create a rule that matches rows with a column that is **less** than the given value
     *
     * @param string $column
     * @param mixed $value
     *
     * @return Condition
     */
    public static function lessThan(string $column, mixed $value): Condition
    {
        return new LessThan($column, $value);
    }

    /**
     * Return whether the given rule's value is less than the given item's value
     *
     * @param LessThan $rule
     * @param object $row
     *
     * @return bool
     */
    protected function matchLessThan(LessThan $rule, object $row): bool
    {
        $rowValue = $this->extractValue($rule->getColumn(), $row);
        $value = $rule->getValue();

        return $rowValue !== null && $value !== null && $rowValue < $value;
    }

    /**
     * Create a rule that matches rows with a column that is **greater** than or **equal** to the given value
     *
     * @param string $column
     * @param mixed $value
     *
     * @return Condition
     */
    public static function greaterThanOrEqual(string $column, mixed $value): Condition
    {
        return new GreaterThanOrEqual($column, $value);
    }

    /**
     * Return whether the given rule's value is greater than or equals the given item's value
     *
     * @param GreaterThanOrEqual $rule
     * @param object $row
     *
     * @return bool
     */
    protected function matchGreaterThanOrEqual(GreaterThanOrEqual $rule, object $row): bool
    {
        $rowValue = $this->extractValue($rule->getColumn(), $row);
        $value = $rule->getValue();

        return $rowValue !== null && $value !== null && $rowValue >= $value;
    }

    /**
     * Create a rule that matches rows with a column that is **less** than or **equal** to the given value
     *
     * @param string $column
     * @param mixed $value
     *
     * @return Condition
     */
    public static function lessThanOrEqual(string $column, mixed $value): Condition
    {
        return new LessThanOrEqual($column, $value);
    }

    /**
     * Return whether the given rule's value is less than or equals the given item's value
     *
     * @param LessThanOrEqual $rule
     * @param object $row
     *
     * @return bool
     */
    protected function matchLessThanOrEqual(LessThanOrEqual $rule, object $row): bool
    {
        $rowValue = $this->extractValue($rule->getColumn(), $row);
        $value = $rule->getValue();

        return $rowValue !== null && $value !== null && $rowValue <= $value;
    }

    /**
     * Perform the appropriate match for the given rule on the given item
     *
     * @param Rule $rule
     * @param object $row
     *
     * @return bool
     */
    protected function performMatch(Rule $rule, object $row): bool
    {
        return match (true) {
            $rule instanceof All                => $this->matchAll($rule, $row),
            $rule instanceof Any                => $this->matchAny($rule, $row),
            $rule instanceof Like               => $this->matchSimilar($rule, $row),
            $rule instanceof Equal              => $this->matchEqual($rule, $row),
            $rule instanceof GreaterThan        => $this->matchGreaterThan($rule, $row),
            $rule instanceof GreaterThanOrEqual => $this->matchGreaterThanOrEqual($rule, $row),
            $rule instanceof LessThan           => $this->matchLessThan($rule, $row),
            $rule instanceof LessThanOrEqual    => $this->matchLessThanOrEqual($rule, $row),
            $rule instanceof None               => $this->matchNone($rule, $row),
            $rule instanceof Unequal            => $this->matchUnequal($rule, $row),
            $rule instanceof Unlike             => $this->matchUnlike($rule, $row),
        };
    }

    /**
     * Return a value from the given row suitable to work with
     *
     * @param string $column
     * @param object $row
     *
     * @return mixed
     */
    protected function extractValue(string $column, object $row): mixed
    {
        return $row->$column ?? null;
    }

    /**
     * Normalize type of $value to the one of $rowValue
     *
     * For details on how this works please see the corresponding test
     * {@see \ipl\Tests\Stdlib\FilterTest::testConditionsAreValueTypeAgnostic}.
     *
     * @param mixed $rowValue
     * @param mixed $value
     *
     * @return void
     */
    protected function normalizeTypes(mixed $rowValue, mixed &$value): void
    {
        if ($rowValue === null || $value === null) {
            return;
        }

        if (is_array($rowValue)) {
            if (empty($rowValue)) {
                return;
            }

            $rowValue = array_shift($rowValue);
        }

        if (is_array($value)) {
            if (is_bool($rowValue) && ! empty($value) && is_string(array_values($value)[0])) {
                return;
            }

            $rowValueType = gettype($rowValue);
            foreach ($value as &$val) {
                settype($val, $rowValueType);
            }
        } elseif (! is_bool($rowValue) || ! is_string($value)) {
            settype($value, gettype($rowValue));
        }
    }
}
