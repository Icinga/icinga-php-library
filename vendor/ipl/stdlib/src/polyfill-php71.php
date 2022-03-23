<?php

if (PHP_VERSION_ID < 70100 && ! function_exists('\is_iterable')) {
    /**
     * Verify that the contents of a variable is an iterable value
     *
     * @param mixed $var The value to check
     *
     * @return bool Returns true if var is iterable, false otherwise
     */
    function is_iterable($var)
    {
        return is_array($var) || $var instanceof \Traversable;
    }
}
