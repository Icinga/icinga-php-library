<?php

namespace ipl\Validator;

use ipl\I18n\Translation;

/**
 * Validates whether the value is greater than the given min
 */
class GreaterThanValidator extends BaseValidator
{
    use Translation;

    /** @var mixed Comparison value for greater than */
    protected $min;

    /**
     * Create a new GreaterThanValidator
     *
     * Optional options:
     * - min: (scalar) Comparison value for greater than, default 0
     */
    public function __construct(array $options = [])
    {
        $this->setMin($options['min'] ?? 0);
    }

    /**
     * Get the min option
     *
     * @return mixed
     */
    public function getMin()
    {
        return $this->min;
    }

    /**
     * Set the min option
     *
     * @param mixed $min
     *
     * @return $this
     */
    public function setMin($min): self
    {
        $this->min = $min;

        return $this;
    }

    public function isValid($value)
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
