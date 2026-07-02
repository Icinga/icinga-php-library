<?php

namespace ipl\Validator;

use InvalidArgumentException;

/**
 * Validate whether a value is between the given min and max
 */
class BetweenValidator extends BaseValidator
{
    /** @var int|float Min value */
    protected int|float $min;

    /** @var int|float Max value */
    protected int|float $max;

    /** @var bool Whether min/max comparisons are inclusive; if false, the value may not equal min or max */
    protected bool $inclusive;

    /**
     * Create a new BetweenValidator
     *
     * Required options:
     *
     * - min: (int|float) Minimum border
     * - max: (int|float) Maximum border
     *
     * Optional options:
     *
     * - inclusive: (bool) Whether inclusive border values, default true
     *
     * @param array{min: int|float, max: int|float, inclusive?: bool} $options
     *
     * @throws InvalidArgumentException When required option is missing
     */
    public function __construct(array $options)
    {
        if (! isset($options['min'], $options['max'])) {
            throw new InvalidArgumentException("Missing option. 'min' and 'max' has to be given");
        }

        $this->setMin($options['min'])
            ->setMax($options['max'])
            ->setInclusive($options['inclusive'] ?? true);
    }

    /**
     * Get the min option
     *
     * @return int|float
     */
    public function getMin(): int|float
    {
        return $this->min;
    }

    /**
     * Set the min option
     *
     * @param int|float $min
     *
     * @return $this
     */
    public function setMin(int|float $min): static
    {
        $this->min = $min;

        return $this;
    }

    /**
     * Get the max option
     *
     * @return int|float
     */
    public function getMax(): int|float
    {
        return $this->max;
    }

    /**
     * Set the max option
     *
     * @param int|float $max
     *
     * @return $this
     */
    public function setMax(int|float $max): static
    {
        $this->max = $max;

        return $this;
    }

    /**
     * Get the inclusive option
     *
     * @return bool
     */
    public function getInclusive(): bool
    {
        return $this->inclusive;
    }

    /**
     * Set the inclusive option
     *
     * @param bool $inclusive
     *
     * @return $this
     */
    public function setInclusive(bool $inclusive = true): static
    {
        $this->inclusive = $inclusive;

        return $this;
    }

    /**
     * Check whether the value is between the min and max
     *
     * @param int|float $value
     *
     * @return bool
     */
    public function isValid($value): bool
    {
        // Reset messages from a previous isValid() call.
        $this->clearMessages();

        if ($this->getInclusive()) {
            if ($this->getMin() > $value || $value > $this->getMax()) {
                $this->addMessage(sprintf(
                    $this->translate("'%s' is not between '%s' and '%s', inclusively"),
                    $value,
                    $this->getMin(),
                    $this->getMax()
                ));

                return false;
            }
        } elseif ($this->getMin() >= $value || $value >= $this->getMax()) {
            $this->addMessage(sprintf(
                $this->translate("'%s' is not between '%s' and '%s'"),
                $value,
                $this->getMin(),
                $this->getMax()
            ));

            return false;
        }

        return true;
    }
}
