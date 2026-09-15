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
     * @param iterable $traversable
     * @param mixed $needle Might also be a closure
     * @param bool $caseSensitive Whether strings should be compared case-sensitive
     *
     * @return bool
     */
    public static function contains(iterable $traversable, mixed $needle, bool $caseSensitive = true): bool
    {
        return self::find($traversable, $needle, $caseSensitive)[0] !== null;
    }

    /**
     * Search in the traversable for the given needle and return its key and value
     *
     * @param iterable $traversable
     * @param mixed $needle Might also be a closure
     * @param bool $caseSensitive Whether strings should be compared case-sensitive
     *
     * @return array{0: mixed, 1: mixed} The found key and value, or [null, null] if nothing is found
     */
    public static function find(iterable $traversable, mixed $needle, bool $caseSensitive = true): array
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
     * @param iterable $traversable
     * @param mixed $needle Might also be a closure
     * @param bool $caseSensitive Whether strings should be compared case-sensitive
     *
     * @return mixed|null Null if nothing is found
     */
    public static function findKey(iterable $traversable, mixed $needle, bool $caseSensitive = true): mixed
    {
        return self::find($traversable, $needle, $caseSensitive)[0];
    }

    /**
     * Search in the traversable for the given needle and return its value
     *
     * @param iterable $traversable
     * @param mixed $needle Might also be a closure
     * @param bool $caseSensitive Whether strings should be compared case-sensitive
     *
     * @return mixed|null Null if nothing is found
     */
    public static function findValue(iterable $traversable, mixed $needle, bool $caseSensitive = true): mixed
    {
        $usesCallback = $needle instanceof Closure;
        if (! $usesCallback && $caseSensitive && is_array($traversable)) {
            return $traversable[$needle] ?? null;
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
