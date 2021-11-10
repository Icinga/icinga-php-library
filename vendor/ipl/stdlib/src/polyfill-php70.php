<?php

if (PHP_VERSION_ID < 70000 && ! function_exists('\random_bytes')) {
    /**
     * Generates cryptographically secure pseudo-random bytes
     *
     * @param int $length The length of the random string that should be returned in bytes.
     *
     * @return string Returns a string containing the requested number of cryptographically secure random bytes.
     *
     * @throws Exception if it was not possible to gather sufficient entropy.
     */
    function random_bytes($length)
    {
        $bytes = openssl_random_pseudo_bytes($length, $strong);
        if (! $strong) {
            throw new Exception('Unable to generate a cryptographically strong result');
        }

        return $bytes;
    }
}
