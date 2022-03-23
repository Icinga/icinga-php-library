<?php

namespace ipl\Stdlib;

use Exception;
use InvalidArgumentException;
use ipl\Stdlib\Filter\All;
use ipl\Stdlib\Filter\Any;
use ipl\Stdlib\Filter\Chain;
use ipl\Stdlib\Filter\Condition;
use ipl\Stdlib\Filter\Equal;
use ipl\Stdlib\Filter\GreaterThan;
use ipl\Stdlib\Filter\GreaterThanOrEqual;
use ipl\Stdlib\Filter\LessThan;
use ipl\Stdlib\Filter\LessThanOrEqual;
use ipl\Stdlib\Filter\None;
use ipl\Stdlib\Filter\Rule;
use ipl\Stdlib\Filter\Unequal;

class Filter
{
    /**
     * protected - This is only a factory class
     */
    protected function __construct()
    {
    }

    /**
     * Return whether the given rule matches the given item
     *
     * @param Rule $rule
     * @param array|object $row
     *
     * @return bool
     */
    public static function match(Rule $rule, $row)
    {
        if (! is_object($row)) {
            if (is_array($row)) {
                $row = (object) $row;
            } else {
                throw new InvalidArgumentException(sprintf(
                    'Object or array expected, got %s instead',
                    get_php_type($row)
                ));
            }
        }

        return (new self())->performMatch($rule, $row);
    }

    /**
     * Create a rule that matches if **all** of the given rules do
     *
     * @param Rule ...$rules
     *
     * @return Chain
     */
    public static function all(Rule ...$rules)
    {
        return new All(...$rules);
    }

    /**
     * Return whether the given rules all match the given item
     *
     * @param All $rules
     * @param object $row
     *
     * @return bool
     */
    protected function matchAll(All $rules, $row)
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
     * @param Rule ...$rules
     *
     * @return Chain
     */
    public static function any(Rule ...$rules)
    {
        return new Any(...$rules);
    }

    /**
     * Return whether any of the given rules match the given item
     *
     * @param Any $rules
     * @param object $row
     *
     * @return bool
     */
    protected function matchAny(Any $rules, $row)
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
     * @param Rule ...$rules
     *
     * @return Chain
     */
    public static function none(Rule ...$rules)
    {
        return new None(...$rules);
    }

    /**
     * Return whether none of the given rules match the given item
     *
     * @param None $rules
     * @param object $row
     *
     * @return bool
     */
    protected function matchNone(None $rules, $row)
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
     * Performs a wildcard search if the value contains asterisks.
     *
     * @param string $column
     * @param array|bool|float|int|string $value
     *
     * @return Condition
     */
    public static function equal($column, $value)
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
    protected function matchEqual($rule, $row)
    {
        if (! $rule instanceof Equal && ! $rule instanceof Unequal) {
            throw new InvalidArgumentException(sprintf(
                'Rule must be of type %s or %s, got %s instead',
                Equal::class,
                Unequal::class,
                get_php_type($rule)
            ));
        }

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
     * Apply equality matching rules on the given row value
     *
     * @param mixed $value
     * @param mixed $rowValue
     * @param bool $ignoreCase
     *
     * @return bool
     */
    protected function performEqualityMatch($value, $rowValue, $ignoreCase = false)
    {
        if ($ignoreCase && is_string($rowValue)) {
            $rowValue = strtolower($rowValue);
            $value = is_array($value)
                ? array_map('strtolower', $value)
                : strtolower($value);
        }

        if (is_array($value)) {
            return in_array($rowValue, $value, true);
        } elseif (! is_string($value)) {
            if (is_string($rowValue)) {
                $value = (string) $value;
            } else {
                return $rowValue === $value;
            }
        }

        $wildcardSubSegments = preg_split('~\*~', $value);
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
     * Performs a wildcard search if the value contains asterisks.
     *
     * @param string $column
     * @param array|bool|float|int|string $value
     *
     * @return Condition
     */
    public static function unequal($column, $value)
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
    protected function matchUnequal(Unequal $rule, $row)
    {
        return ! $this->matchEqual($rule, $row);
    }

    /**
     * Create a rule that matches rows with a column that is **greater** than the given value
     *
     * @param string $column
     * @param float|int|string $value
     *
     * @return Condition
     */
    public static function greaterThan($column, $value)
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
    protected function matchGreaterThan(GreaterThan $rule, $row)
    {
        return $this->extractValue($rule->getColumn(), $row) > $rule->getValue();
    }

    /**
     * Create a rule that matches rows with a column that is **less** than the given value
     *
     * @param string $column
     * @param float|int|string $value
     *
     * @return Condition
     */
    public static function lessThan($column, $value)
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
    protected function matchLessThan(LessThan $rule, $row)
    {
        $rowValue = $this->extractValue($rule->getColumn(), $row);
        if ($rowValue === null) {
            return false;
        }

        return $rowValue < $rule->getValue();
    }

    /**
     * Create a rule that matches rows with a column that is **greater** than or **equal** to the given value
     *
     * @param string $column
     * @param float|int|string $value
     *
     * @return Condition
     */
    public static function greaterThanOrEqual($column, $value)
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
    protected function matchGreaterThanOrEqual(GreaterThanOrEqual $rule, $row)
    {
        return $this->extractValue($rule->getColumn(), $row) >= $rule->getValue();
    }

    /**
     * Create a rule that matches rows with a column that is **less** than or **equal** to the given value
     *
     * @param string $column
     * @param float|int|string $value
     *
     * @return Condition
     */
    public static function lessThanOrEqual($column, $value)
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
    protected function matchLessThanOrEqual(LessThanOrEqual $rule, $row)
    {
        $rowValue = $this->extractValue($rule->getColumn(), $row);
        if ($rowValue === null) {
            return false;
        }

        return $rowValue <= $rule->getValue();
    }

    /**
     * Perform the appropriate match for the given rule on the given item
     *
     * @param Rule $rule
     * @param object $row
     *
     * @return bool
     */
    protected function performMatch(Rule $rule, $row)
    {
        switch (true) {
            case $rule instanceof All:
                return $this->matchAll($rule, $row);
            case $rule instanceof Any:
                return $this->matchAny($rule, $row);
            case $rule instanceof Equal:
                return $this->matchEqual($rule, $row);
            case $rule instanceof GreaterThan:
                return $this->matchGreaterThan($rule, $row);
            case $rule instanceof GreaterThanOrEqual:
                return $this->matchGreaterThanOrEqual($rule, $row);
            case $rule instanceof LessThan:
                return $this->matchLessThan($rule, $row);
            case $rule instanceof LessThanOrEqual:
                return $this->matchLessThanOrEqual($rule, $row);
            case $rule instanceof None:
                return $this->matchNone($rule, $row);
            case $rule instanceof Unequal:
                return $this->matchUnequal($rule, $row);
            default:
                throw new InvalidArgumentException(sprintf(
                    'Unable to match filter. Rule type %s is unknown',
                    get_class($rule)
                ));
        }
    }

    /**
     * Return a value from the given row suitable to work with
     *
     * @param string $column
     * @param object $row
     *
     * @return mixed
     */
    protected function extractValue($column, $row)
    {
        try {
            return $row->{$column};
        } catch (Exception $_) {
            return null;
        }
    }

    /**
     * Normalize type of $value to the one of $rowValue
     *
     * For details on how this works please see the corresponding test
     * {@see \ipl\Tests\Stdlib\FilterTest::testConditionsAreValueTypeAgnostic}
     *
     * @param mixed $rowValue
     * @param mixed $value
     *
     * @return void
     */
    protected function normalizeTypes($rowValue, &$value)
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
