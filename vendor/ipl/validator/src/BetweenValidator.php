<?php

namespace ipl\Validator;

use Exception;
use ipl\I18n\Translation;

/**
 * Validates whether value is between the given min and max
 */
class BetweenValidator extends BaseValidator
{
    use Translation;

    /** @var mixed Min value */
    protected $min;

    /** @var mixed Max value */
    protected $max;

    /**
     * Whether to do inclusive comparisons, allowing equivalence to min and/or max
     *
     * If false, then strict comparisons are done, and the value may equal neither
     * the min nor max options
     *
     * @var boolean
     */
    protected $inclusive;

    /**
     * Create a new BetweenValidator
     *
     * Required options:
     *
     * - min: (scalar) Minimum border
     * - max: (scalar) Maximum border
     *
     * Optional options:
     *
     * - inclusive: (bool) Whether inclusive border values, default true
     *
     * @param array $options
     *
     * @throws Exception When required option is missing
     */
    public function __construct(array $options)
    {
        if (! isset($options['min'], $options['max'])) {
            throw new Exception("Missing option. 'min' and 'max' has to be given");
        }

        $this->setMin($options['min'])
            ->setMax($options['max'])
            ->setInclusive($options['inclusive'] ?? true);
    }

    /**
     * Return the min option
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
     * @param  mixed $min
     *
     * @return $this
     */
    public function setMin($min): self
    {
        $this->min = $min;

        return $this;
    }

    /**
     * Return the max option
     *
     * @return mixed
     */
    public function getMax()
    {
        return $this->max;
    }

    /**
     * Set the max option
     *
     * @param  mixed $max
     *
     * @return $this
     */
    public function setMax($max): self
    {
        $this->max = $max;

        return $this;
    }

    /**
     * Return the inclusive option
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
     * @param  bool $inclusive
     *
     * @return $this
     */
    public function setInclusive($inclusive = true): self
    {
        $this->inclusive = (bool) $inclusive;

        return $this;
    }

    public function isValid($value)
    {
        // Multiple isValid() calls must not stack validation messages
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
