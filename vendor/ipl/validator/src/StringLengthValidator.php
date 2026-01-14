<?php

namespace ipl\Validator;

use InvalidArgumentException;
use ipl\I18n\Translation;
use LogicException;

/**
 * Validates string length with given options
 */
class StringLengthValidator extends BaseValidator
{
    use Translation;

    /** @var int Minimum required length */
    protected $min;

    /** @var ?int Maximum required length */
    protected $max;

    /** @var ?string Encoding to use */
    protected $encoding;

    /**
     * Create a new StringLengthValidator
     *
     * Optional options:
     * - min: (int) Minimum required string length, default 0
     * - max: (int) Maximum required string length, default none
     * - encoding: (string) Encoding type, default none
     *
     * @param array{min?: int, max?: int, encoding?: string} $options
     */
    public function __construct(array $options = [])
    {
        $this
            ->setMin($options['min'] ?? 0)
            ->setMax($options['max'] ?? null)
            ->setEncoding($options['encoding'] ?? null);
    }

    /**
     * Get the minimum required string length
     *
     * @return int
     */
    public function getMin(): int
    {
        return $this->min;
    }

    /**
     * Set the minimum required string length
     *
     * @param int $min
     *
     * @return $this
     *
     * @throws LogicException When the $min is greater than the $max value
     */
    public function setMin(int $min): self
    {
        if ($this->getMax() !== null && $min > $this->getMax()) {
            throw new LogicException(
                sprintf(
                    'The min must be less than or equal to the max length, but min: %d and max: %d given.',
                    $min,
                    $this->getMax()
                )
            );
        }

        $this->min = $min;

        return $this;
    }

    /**
     * Get the maximum required string length
     *
     * @return ?int
     */
    public function getMax(): ?int
    {
        return $this->max;
    }

    /**
     * Set the minimum required string length
     *
     * @param ?int $max
     *
     * @return $this
     *
     * @throws LogicException When the $min is greater than the $max value
     */
    public function setMax(?int $max): self
    {
        if ($max !== null && $this->getMin() > $max) {
            throw new LogicException(
                sprintf(
                    'The min must be less than or equal to the max length, but min: %d and max: %d given.',
                    $this->getMin(),
                    $max
                )
            );
        }

        $this->max = $max;

        return $this;
    }

    /**
     * Get the encoding type to use
     *
     * @return ?string
     */
    public function getEncoding(): ?string
    {
        return $this->encoding;
    }

    /**
     * Set the encoding type to use
     *
     * @param ?string $encoding
     *
     * @return $this
     */
    public function setEncoding(?string $encoding): self
    {
        if ($encoding !== null) {
            $availableEncodings = array_map('strtolower', mb_list_encodings());
            if (! in_array(strtolower($encoding), $availableEncodings, true)) {
                throw new InvalidArgumentException(
                    sprintf('Given encoding "%s" is not supported on this OS!', $encoding)
                );
            }
        }

        $this->encoding = $encoding;

        return  $this;
    }

    /**
     * @param string $value
     *
     * @return bool
     */
    public function isValid($value): bool
    {
        // Multiple isValid() calls must not stack validation messages
        $this->clearMessages();

        if ($encoding = $this->getEncoding()) { // because encoding is only nullable in php >= 8.0
            $length = mb_strlen($value, $encoding);
        } else {
            $length = mb_strlen($value);
        }

        if ($length < $this->getMin()) {
            $this->addMessage(sprintf(
                $this->translate('String should be %d characters long, %d given'),
                $this->getMin(),
                $length
            ));

            return false;
        }

        if ($this->getMax() && $this->getMax() < $length) {
            $this->addMessage(sprintf(
                $this->translate('String should be %d characters long, %d given'),
                $this->getMax(),
                $length
            ));

            return false;
        }

        return true;
    }
}
