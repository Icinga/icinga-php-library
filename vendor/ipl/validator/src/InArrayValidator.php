<?php

namespace ipl\Validator;

use ipl\I18n\Translation;

/**
 * Validate if specific single or multiple values exist in an array
 */
class InArrayValidator extends BaseValidator
{
    use Translation;

    /** @var array The array */
    protected $haystack;

    /** @var bool Whether the types of the needle in the haystack should also match */
    protected $strict = false;

    /**
     * Create a new InArray validator
     *
     * **Optional options:**
     *
     * * `haystack`: (`array`) The array
     * * `strict`: (`bool`) Whether the types of the needle in the haystack should also match, default `false`
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        if (isset($options['haystack'])) {
            $this->setHaystack($options['haystack']);
        }

        $this->setStrict($options['strict'] ?? false);
    }

    /**
     * Get the haystack
     *
     * @return array
     */
    public function getHaystack(): array
    {
        return $this->haystack ?? [];
    }

    /**
     * Set the haystack
     *
     * @param array $haystack
     *
     * @return $this
     */
    public function setHaystack(array $haystack): self
    {
        $this->haystack = $haystack;

        return $this;
    }

    /**
     * Get whether the types of the needle in the haystack should also match
     *
     * @return bool
     */
    public function isStrict(): bool
    {
        return $this->strict;
    }

    /**
     * Set whether the types of the needle in the haystack should also match
     *
     * @param bool $strict
     *
     * @return $this
     */
    public function setStrict(bool $strict = true): self
    {
        $this->strict = $strict;

        return $this;
    }

    public function isValid($value)
    {
        // Multiple isValid() calls must not stack validation messages
        $this->clearMessages();

        $notInArray = $this->findInvalid((array) $value);

        if (empty($notInArray)) {
            return true;
        }

        $this->addMessage(sprintf(
            $this->translatePlural(
                "%s was not found in the haystack",
                "%s were not found in the haystack",
                count($notInArray)
            ),
            implode(', ', $notInArray)
        ));

        return false;
    }

    /**
     * Get the values from the specified array that are not present in the haystack
     *
     * @param array $values
     *
     * @return array Values not found in the haystack
     */
    protected function findInvalid(array $values = []): array
    {
        $notInArray = [];
        foreach ($values as $val) {
            if (! in_array($val, $this->getHaystack(), $this->isStrict())) {
                $notInArray[] = $val;
            }
        }

        return $notInArray;
    }
}
