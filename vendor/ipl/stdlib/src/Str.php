<?php

namespace ipl\Stdlib;

/**
 * Collection of string manipulation functions
 */
class Str
{
    /**
     * Convert the given string to camel case
     *
     * The given string may be delimited by the following characters: '_' (underscore), '-' (dash), ' ' (space).
     *
     * @param string $subject
     *
     * @return string
     */
    public static function camel($subject)
    {
        $normalized = str_replace(['-', '_'], ' ', $subject);

        return lcfirst(str_replace(' ', '', ucwords(strtolower($normalized))));
    }

    /**
     * Check if the given string starts with the specified substring
     *
     * @param string $subject
     * @param string $start
     * @param bool   $caseSensitive
     *
     * @return bool
     */
    public static function startsWith($subject, $start, $caseSensitive = true)
    {
        if (! $caseSensitive) {
            return strncasecmp($subject, $start, strlen($start)) === 0;
        }

        return substr($subject, 0, strlen($start)) === $start;
    }

    /**
     * Split string into an array padded to the size specified by limit
     *
     * This method is a perfect fit if you need default values for symmetric array destructuring.
     *
     * @param string $subject
     * @param string $delimiter
     * @param int    $limit
     * @param mixed  $default
     *
     * @return array
     */
    public static function symmetricSplit($subject, $delimiter, $limit, $default = null)
    {
        return array_pad(explode($delimiter, $subject, $limit), $limit, $default);
    }

    /**
     * Split string into an array and trim spaces
     *
     * @param string $subject
     * @param string $delimiter
     * @param int    $limit
     *
     * @return array
     */
    public static function trimSplit($subject, $delimiter = ',', $limit = null)
    {
        if ($limit !== null) {
            $exploded = explode($delimiter, $subject, $limit);
        } else {
            $exploded = explode($delimiter, $subject);
        }

        return array_map('trim', $exploded);
    }
}
