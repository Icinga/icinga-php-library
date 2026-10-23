<?php

namespace ipl\Stdlib;

use Generator;
use IteratorIterator;
use stdClass;
use Traversable;

/**
 * Detect and return the PHP type of the given subject
 *
 * If subject is an object, the name of the object's class is returned, otherwise the subject's type.
 *
 * @param mixed $subject
 *
 * @return string
 */
function get_php_type(mixed $subject): string
{
    return is_object($subject) ? get_class($subject) : gettype($subject);
}

/**
 * Get the array value of the given subject
 *
 * @param iterable|stdClass $subject
 *
 * @return array
 */
function arrayval(iterable|stdClass $subject): array
{
    if (is_array($subject)) {
        return $subject;
    }

    if ($subject instanceof stdClass) {
        return (array) $subject;
    }

    // Also works for generators.
    return iterator_to_array($subject);
}

/**
 * Get the first key of an iterable
 *
 * @param iterable $iterable
 *
 * @return int|string|null The first key of the iterable if it is not empty, null otherwise
 */
function iterable_key_first(iterable $iterable): int|string|null
{
    foreach ($iterable as $key => $_) {
        return $key;
    }

    return null;
}

/**
 * Get the first value of an iterable
 *
 * @param iterable $iterable
 *
 * @return mixed|null The first value of the iterable if it is not empty, null otherwise
 */
function iterable_value_first(iterable $iterable): mixed
{
    foreach ($iterable as $value) {
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
 * @return Generator<mixed, array>
 */
function yield_groups(Traversable $traversable, callable $groupBy): Generator
{
    $iterator = new IteratorIterator($traversable);
    $iterator->rewind();

    if (! $iterator->valid()) {
        return;
    }

    [$criterion, $v, $k] = array_pad((array) $groupBy($iterator->current(), $iterator->key()), 3, null);
    $group = [$k ?? $iterator->key() => $v ?? $iterator->current()];

    $iterator->next();
    for (; $iterator->valid(); $iterator->next()) {
        [$c, $v, $k] = array_pad((array) $groupBy($iterator->current(), $iterator->key()), 3, null);
        if ($c !== $criterion) {
            yield $criterion => $group;

            $group = [];
            $criterion = $c;
        }

        $group[$k ?? $iterator->key()] = $v ?? $iterator->current();
    }

    yield $criterion => $group;
}
