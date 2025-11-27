<?php

namespace ipl\Stdlib;

use Generator;
use InvalidArgumentException;
use IteratorIterator;
use Traversable;
use stdClass;

/**
 * Detect and return the PHP type of the given subject
 *
 * If subject is an object, the name of the object's class is returned, otherwise the subject's type.
 *
 * @param mixed $subject
 *
 * @return string
 */
function get_php_type($subject)
{
    if (is_object($subject)) {
        return get_class($subject);
    } else {
        return gettype($subject);
    }
}

/**
 * Get the array value of the given subject
 *
 * @param array<mixed>|object|Traversable $subject
 *
 * @return array<mixed>
 *
 * @throws InvalidArgumentException If subject type is invalid
 */
function arrayval($subject)
{
    if (is_array($subject)) {
        return $subject;
    }

    if ($subject instanceof stdClass) {
        return (array) $subject;
    }

    if ($subject instanceof Traversable) {
        // Works for generators too
        return iterator_to_array($subject);
    }

    throw new InvalidArgumentException(sprintf(
        'arrayval expects arrays, objects or instances of Traversable. Got %s instead.',
        get_php_type($subject)
    ));
}

/**
 * Get the first key of an iterable
 *
 * @param iterable<mixed> $iterable
 *
 * @return mixed The first key of the iterable if it is not empty, null otherwise
 */
function iterable_key_first($iterable)
{
    foreach ($iterable as $key => $_) {
        return $key;
    }

    return null;
}

/**
 * Get the first value of an iterable
 *
 * @param iterable<mixed> $iterable
 *
 * @return ?mixed
 */
function iterable_value_first($iterable)
{
    foreach ($iterable as $_ => $value) {
        return $value;
    }

    return null;
}

/**
 * Yield sets of items from a sorted traversable grouped by a specific criterion gathered from a callback
 *
 * The traversable must be sorted by the criterion. The callback must return at least the criterion,
 * but can also return value and key in addition.
 *
 * @param Traversable<mixed, mixed> $traversable
 * @param callable(mixed $value, mixed $key): array{0: mixed, 1?: mixed, 2?: mixed} $groupBy
 *
 * @return Generator
 */
function yield_groups(Traversable $traversable, callable $groupBy): Generator
{
    $iterator = new IteratorIterator($traversable);
    $iterator->rewind();

    if (! $iterator->valid()) {
        return;
    }

    list($criterion, $v, $k) = array_pad((array) $groupBy($iterator->current(), $iterator->key()), 3, null);
    $group = [$k ?? $iterator->key() => $v ?? $iterator->current()];

    $iterator->next();
    for (; $iterator->valid(); $iterator->next()) {
        list($c, $v, $k) = array_pad((array) $groupBy($iterator->current(), $iterator->key()), 3, null);
        if ($c !== $criterion) {
            yield $criterion => $group;

            $group = [];
            $criterion = $c;
        }

        $group[$k ?? $iterator->key()] = $v ?? $iterator->current();
    }

    yield $criterion => $group;
}
