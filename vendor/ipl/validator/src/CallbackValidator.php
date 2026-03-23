<?php

namespace ipl\Validator;

/**
 * Validator that uses a callback for the actual validation
 *
 * # Example Usage
 * ```
 * $dedup = new CallbackValidator(function ($value, CallbackValidator $validator) {
 *     if (already_exists_in_database($value)) {
 *         $validator->addMessage('Record already exists in database');
 *
 *         return false;
 *     }
 *
 *     return true;
 * });
 *
 * $dedup->isValid($id);
 * ```
 */
class CallbackValidator extends BaseValidator
{
    /** @var callable Validation callback */
    protected $callback;

    /**
     * Create a new callback validator
     *
     * @param callable $callback Validation callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function isValid($value)
    {
        // Multiple isValid() calls must not stack validation messages
        $this->clearMessages();

        return call_user_func($this->callback, $value, $this);
    }
}
