<?php

namespace ipl\Validator;

/**
 * Delegate validation to a callable
 *
 * # Example Usage
 *
 *     $dedup = new CallbackValidator(function ($value, CallbackValidator $validator) {
 *         if (already_exists_in_database($value)) {
 *             $validator->addMessage('Record already exists in database');
 *
 *             return false;
 *         }
 *
 *         return true;
 *     });
 *
 *     $dedup->isValid($id);
 */
class CallbackValidator extends BaseValidator
{
    /** @var callable Validation callback */
    protected $callback;

    /**
     * Create a new CallbackValidator
     *
     * @param callable $callback Validation callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * Check whether the value passes the validation callback
     *
     * @param mixed $value
     *
     * @return bool
     */
    public function isValid($value): bool
    {
        // Reset messages from a previous isValid() call.
        $this->clearMessages();

        return call_user_func($this->callback, $value, $this);
    }
}
