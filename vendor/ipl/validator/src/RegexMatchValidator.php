<?php

namespace ipl\Validator;

use InvalidArgumentException;

/**
 * Validate a value against a given regex pattern
 *
 * Available options:
 * - pattern: (string) Regex pattern
 * - notMatchMessage: (string) Message to show when the value isn't valid. If not set, a default message will be used
 */
class RegexMatchValidator extends BaseValidator
{
    /** @var string Regex pattern */
    protected string $pattern;

    /** @var ?string Message to show when the value isn't valid. If not set, a default message will be used */
    protected ?string $notMatchMessage = null;

    /**
     * Create a new RegexMatchValidator
     *
     * @param string|array{pattern: string, notMatchMessage?: string|null} $pattern
     *
     * @throws InvalidArgumentException If the notMatchMessage consists only of whitespace
     * @throws InvalidArgumentException If the pattern is missing or invalid
     */
    public function __construct(string|array $pattern)
    {
        if (is_array($pattern)) {
            $this->pattern = $pattern['pattern'] ?? throw new InvalidArgumentException("Missing option 'pattern'");
            $this->notMatchMessage = $pattern['notMatchMessage'] ?? null;

            if ($this->notMatchMessage !== null && trim($this->notMatchMessage) === '') {
                throw new InvalidArgumentException(
                    "Option 'notMatchMessage' must not be an empty or whitespace-only string"
                );
            }
        } else {
            $this->pattern = $pattern;
        }

        $syntax = new RegexSyntaxValidator();
        if (! $syntax->isValid($this->pattern)) {
            throw new InvalidArgumentException($syntax->getMessages()[0]);
        }
    }

    /**
     * Check whether the value matches the pattern
     *
     * @param string $value
     *
     * @return bool
     */
    public function isValid($value): bool
    {
        // Reset messages from a previous isValid() call.
        $this->clearMessages();

        $result = preg_match($this->pattern, $value);

        if ($result === false) {
            $this->addMessage(preg_last_error_msg());

            return false;
        }

        if (! $result) {
            if ($this->notMatchMessage === null) {
                $this->addMessage(sprintf(
                    $this->translate("'%s' does not match against pattern '%s'"),
                    $value,
                    $this->pattern
                ));
            } else {
                $this->addMessage($this->notMatchMessage);
            }

            return false;
        }

        return true;
    }
}
