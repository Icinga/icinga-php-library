<?php

namespace ipl\Validator;

/**
 * Validates whether the value is greater than the given min
 */
class GreaterThanValidator extends BaseValidator
{
    /** @var int|float Comparison value for greater than */
    protected int|float $min;

    /**
     * Create a new GreaterThanValidator
     *
     * Optional options:
     * - min: (int|float) Comparison value for greater than, default 0
     *
     * @param array{min?: int|float} $options
     */
    public function __construct(array $options = [])
    {
        $this->setMin($options['min'] ?? 0);
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
     * @param int|float $value
     *
     * @return bool
     */
    public function isValid($value): bool
    {
        // Multiple isValid() calls must not stack validation messages
        $this->clearMessages();

        if ($this->getMin() >= $value) {
            $this->addMessage(sprintf(
                $this->translate("'%s' is not greater than '%s'"),
                $value,
                $this->min
            ));

            return false;
        }

        return true;
    }
}
