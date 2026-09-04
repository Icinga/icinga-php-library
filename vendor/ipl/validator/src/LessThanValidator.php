<?php

namespace ipl\Validator;

/**
 * Validate whether a value is less than the given maximum
 */
class LessThanValidator extends BaseValidator
{
    /** @var int|float Comparison value for less than */
    protected int|float $max;

    /**
     * Create a new LessThanValidator
     *
     * Optional options:
     * - max: (int|float) Comparison value for less than, default 0
     *
     * @param array{max?: int|float} $options
     */
    public function __construct(array $options = [])
    {
        $this->setMax($options['max'] ?? 0);
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
     * Check whether the value is less than the maximum
     *
     * @param int|float $value
     *
     * @return bool
     */
    public function isValid($value): bool
    {
        // Reset messages from a previous isValid() call.
        $this->clearMessages();

        if ($this->getMax() <= $value) {
            $this->addMessage(sprintf(
                $this->translate("'%s' is not less than '%s'"),
                $value,
                $this->getMax()
            ));

            return false;
        }

        return true;
    }
}
