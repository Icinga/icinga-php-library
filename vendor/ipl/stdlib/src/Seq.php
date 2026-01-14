<?php

namespace ipl\Stdlib;

use Closure;

/**
 * Collection of utilities for traversables
 */
class Seq
{
    /**
     * Check if the traversable contains the given needle
     *
     * @param array<mixed>|iterable<mixed> $traversable
     * @param mixed $needle Might also be a closure
     * @param bool $caseSensitive Whether strings should be compared case-sensitive
     *
     * @return bool
     */
    public static function contains($traversable, $needle, $caseSensitive = true)
    {
        return self::find($traversable, $needle, $caseSensitive)[0] !== null;
    }

    /**
     * Search in the traversable for the given needle and return its key and value
     *
     * @param array<mixed>|iterable<mixed> $traversable
     * @param mixed $needle Might also be a closure
     * @param bool $caseSensitive Whether strings should be compared case-sensitive
     *
     * @return array<mixed> An array with two entries, the first is the key, then the value.
     *                      Both are null if nothing is found.
     */
    public static function find($traversable, $needle, $caseSensitive = true)
    {
        $usesCallback = $needle instanceof Closure;
        if (! $usesCallback && $caseSensitive && is_array($traversable)) {
            return [array_search($needle, $traversable, true), $needle];
        }

        if (! $caseSensitive && is_string($needle) && ! $usesCallback) {
            $needle = strtolower($needle);
        }

        foreach ($traversable as $key => $item) {
            $originalItem = $item;
            if (! $caseSensitive && is_string($item)) {
                $item = strtolower($item);
            }

            if ($usesCallback) {
                /** @var Closure $needle */
                if ($needle($item)) {
                    return [$key, $originalItem];
                }
            } elseif ($item === $needle) {
                return [$key, $originalItem];
            }
        }

        return [null, null];
    }

    /**
     * Search in the traversable for the given needle and return its key
     *
     * @param array<mixed>|iterable<mixed> $traversable
     * @param mixed $needle Might also be a closure
     * @param bool $caseSensitive Whether strings should be compared case-sensitive
     *
     * @return mixed|null Null if nothing is found
     */
    public static function findKey($traversable, $needle, $caseSensitive = true)
    {
        return self::find($traversable, $needle, $caseSensitive)[0];
    }

    /**
     * Search in the traversable for the given needle and return its value
     *
     * @param array<mixed>|iterable<mixed> $traversable
     * @param mixed $needle Might also be a closure
     * @param bool $caseSensitive Whether strings should be compared case-sensitive
     *
     * @return mixed|null Null if nothing is found
     */
    public static function findValue($traversable, $needle, $caseSensitive = true)
    {
        $usesCallback = $needle instanceof Closure;
        if (! $usesCallback && $caseSensitive && is_array($traversable)) {
            return isset($traversable[$needle]) ? $traversable[$needle] : null;
        }

        if (! $caseSensitive && is_string($needle) && ! $usesCallback) {
            $needle = strtolower($needle);
        }

        foreach ($traversable as $key => $item) {
            if (! $caseSensitive && is_string($key)) {
                $key = strtolower($key);
            }

            if ($usesCallback) {
                /** @var Closure $needle */
                if ($needle($key)) {
                    return $item;
                }
            } elseif ($key === $needle) {
                return $item;
            }
        }

        return null;
    }
}
