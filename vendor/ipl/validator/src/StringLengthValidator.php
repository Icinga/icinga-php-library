<?php

namespace ipl\Validator;

use InvalidArgumentException;

/**
 * Validate a string's length
 */
class StringLengthValidator extends BaseValidator
{
    /** @var int Minimum required length */
    protected int $min;

    /** @var ?int Maximum required length */
    protected ?int $max = null;

    /** @var ?string Encoding to use */
    protected ?string $encoding;

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
     * @throws InvalidArgumentException When the $min is greater than the $max value
     */
    public function setMin(int $min): static
    {
        if ($this->getMax() !== null && $min > $this->getMax()) {
            throw new InvalidArgumentException(
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
     * Set the maximum required string length
     *
     * @param ?int $max
     *
     * @return $this
     *
     * @throws InvalidArgumentException When the $min is greater than the $max value
     */
    public function setMax(?int $max): static
    {
        if ($max !== null && $this->getMin() > $max) {
            throw new InvalidArgumentException(
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
     *
     * @throws InvalidArgumentException When the given encoding is not supported
     */
    public function setEncoding(?string $encoding): static
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

        return $this;
    }

    /**
     * Check whether the string's length is within the configured min and max bounds
     *
     * @param string $value
     *
     * @return bool
     */
    public function isValid($value): bool
    {
        // Reset messages from a previous isValid() call.
        $this->clearMessages();

        if ($encoding = $this->getEncoding()) { // Encoding is nullable only in PHP >= 8.0.
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
