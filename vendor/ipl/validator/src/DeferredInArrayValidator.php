<?php

namespace ipl\Validator;

/**
 * Validates whether the value exists in the haystack created by the callback
 */
class DeferredInArrayValidator extends InArrayValidator
{
    /** @var callable Callback to create the haystack array */
    protected $callback;

    /**
     * Create a new deferredInArray validator
     *
     * **Required parameter:**
     *
     * - `callback`: (`callable`) The callback to create the haystack
     *
     * **Optional parameter:**
     *
     *  *options: (`array`) Following option can be defined:*
     *
     *  * `strict`: (`bool`) Whether the types of the needle in the haystack should also match, default `false`
     *
     * @param callable $callback The callback to create the haystack
     * @param array{haystack?: mixed[], strict?: bool} $options
     */
    public function __construct(callable $callback, array $options = [])
    {
        $this->callback = $callback;

        parent::__construct($options);
    }

    public function getHaystack(): array
    {
        return $this->haystack ?? call_user_func($this->callback);
    }

    /**
     * Set the callback
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function setCallback(callable $callback): static
    {
        $this->haystack = null;
        $this->callback = $callback;

        return $this;
    }
}
