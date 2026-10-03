<?php

namespace ipl\Stdlib\Contract;

/**
 * Validate values and collect error messages
 */
interface Validator
{
    /**
     * Check whether the given value is valid
     *
     * @param mixed $value
     *
     * @return bool
     */
    public function isValid($value);

    /**
     * Get the validation error messages
     *
     * @return string[]
     */
    public function getMessages();
}
