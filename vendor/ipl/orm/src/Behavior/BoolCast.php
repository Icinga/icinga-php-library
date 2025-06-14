<?php

namespace ipl\Orm\Behavior;

use InvalidArgumentException;
use ipl\Orm\Contract\PropertyBehavior;
use ipl\Orm\Exception\ValueConversionException;

use function ipl\Stdlib\get_php_type;

/**
 * Convert specific database values from and to boolean
 *
 * To unify the support of boolean values in different database systems,
 * specific database values are converted to and from boolean values,
 * e.g. by default `n` is converted to `false` and `y` to `true` and vice versa respectively,
 * which could be stored as `ENUM('n', 'y')`.
 */
class BoolCast extends PropertyBehavior
{
    /** @var mixed Database value for boolean `false` */
    protected $falseValue = 'n';

    /** @var mixed Database value for boolean `true` */
    protected $trueValue = 'y';

    /** @var bool Whether to throw an exception if the value is not equal to the value for false or true */
    protected $strict = true;

    /**
     * Get the database value representing boolean `false`
     *
     * @return mixed
     */
    public function getFalseValue()
    {
        return $this->falseValue;
    }

    /**
     * Set the database value representing boolean `false`
     *
     * @param mixed $falseValue
     *
     * @return $this
     */
    public function setFalseValue($falseValue): self
    {
        $this->falseValue = $falseValue;

        return $this;
    }

    /**
     * Get the database value representing boolean `true`
     *
     * @return mixed
     */
    public function getTrueValue()
    {
        return $this->trueValue;
    }

    /**
     * Get the database value representing boolean `true`
     *
     * @param mixed $trueValue
     *
     * @return $this
     */
    public function setTrueValue($trueValue): self
    {
        $this->trueValue = $trueValue;

        return $this;
    }

    /**
     * Get whether to throw an exception if the value is not equal to the value for false or true
     *
     * @return bool
     */
    public function isStrict(): bool
    {
        return $this->strict;
    }

    /**
     * Set whether to throw an exception if the value is not equal to the value for false or true
     *
     * @param bool $strict
     *
     * @return $this
     */
    public function setStrict(bool $strict): self
    {
        $this->strict = $strict;

        return $this;
    }

    public function fromDb($value, $key, $_)
    {
        switch (true) {
            case $this->trueValue === $value:
                return true;
            case $this->falseValue === $value:
                return false;
            default:
                if ($this->isStrict() && $value !== null) {
                    throw new InvalidArgumentException(sprintf(
                        'Expected %s or %s, got %s instead',
                        $this->trueValue,
                        $this->falseValue,
                        $value
                    ));
                }

                return $value;
        }
    }

    public function toDb($value, $key, $_)
    {
        if ($value === null) {
            return null;
        }

        if (! is_bool($value)) {
            if (
                $this->isStrict()
                && $value !== '*'
                && $value !== $this->getFalseValue()
                && $value !== $this->getTrueValue()
            ) {
                throw new ValueConversionException(sprintf(
                    'Expected bool, got %s instead',
                    get_php_type($value)
                ));
            }

            return $value;
        }

        return $value ? $this->trueValue : $this->falseValue;
    }
}
