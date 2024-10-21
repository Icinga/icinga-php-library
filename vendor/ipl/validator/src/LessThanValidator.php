<?php

namespace ipl\Validator;

use ipl\I18n\Translation;

/**
 * Validates whether the value is less than the given max
 */
class LessThanValidator extends BaseValidator
{
    use Translation;

    /** @var int|float Comparison value for less than */
    protected $max;

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
    public function getMax()
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
    public function setMax($max): self
    {
        $this->max = $max;

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
