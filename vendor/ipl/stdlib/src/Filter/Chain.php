<?php

namespace ipl\Stdlib\Filter;

use ArrayIterator;
use Countable;
use Generator;
use IteratorAggregate;
use OutOfBoundsException;
use Traversable;

/**
 * Abstract filter chain that holds an ordered list of rules
 *
 * @implements IteratorAggregate<int, Rule>
 */
abstract class Chain implements Rule, MetaDataProvider, IteratorAggregate, Countable
{
    use MetaData;

    /** @var array<int, Rule> */
    protected array $rules = [];

    /**
     * Create a new Chain
     *
     * @param Rule ...$rules
     */
    public function __construct(Rule ...$rules)
    {
        foreach ($rules as $rule) {
            $this->add($rule);
        }
    }

    /**
     * Clone this chain's meta data and rules
     */
    public function __clone()
    {
        if ($this->metaData !== null) {
            $this->metaData = clone $this->metaData;
        }

        foreach ($this->rules as $i => $rule) {
            $this->rules[$i] = clone $rule;
        }
    }

    /**
     * Get an iterator for this chain's rules
     *
     * @return ArrayIterator<int, Rule>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->rules);
    }

    /**
     * Yield all rules of this and nested chains in a flat sequence
     *
     * @return Generator<Rule>
     */
    public function yieldRules(): Generator
    {
        foreach ($this->rules as $rule) {
            if ($rule instanceof self) {
                yield from $rule->yieldRules();
            } else {
                yield $rule;
            }
        }
    }

    /**
     * Add a rule to this chain
     *
     * @param Rule $rule
     *
     * @return $this
     */
    public function add(Rule $rule): static
    {
        $this->rules[] = $rule;

        return $this;
    }

    /**
     * Prepend a rule to an existing rule in this chain
     *
     * @param Rule $rule
     * @param Rule $before
     *
     * @return $this
     *
     * @throws OutOfBoundsException If the reference rule is not found
     */
    public function insertBefore(Rule $rule, Rule $before): static
    {
        $ruleAt = array_search($before, $this->rules, true);
        if ($ruleAt === false) {
            throw new OutOfBoundsException('Reference rule not found');
        }

        array_splice($this->rules, $ruleAt, 0, [$rule]);

        return $this;
    }

    /**
     * Append a rule to an existing rule in this chain
     *
     * @param Rule $rule
     * @param Rule $after
     *
     * @return $this
     *
     * @throws OutOfBoundsException If the reference rule is not found
     */
    public function insertAfter(Rule $rule, Rule $after): static
    {
        $ruleAt = array_search($after, $this->rules, true);
        if ($ruleAt === false) {
            throw new OutOfBoundsException('Reference rule not found');
        }

        array_splice($this->rules, $ruleAt + 1, 0, [$rule]);

        return $this;
    }

    /**
     * Get whether this chain contains the given rule
     *
     * @param Rule $rule
     *
     * @return bool
     */
    public function has(Rule $rule): bool
    {
        return in_array($rule, $this->rules, true);
    }

    /**
     * Replace a rule with another one in this chain
     *
     * @param Rule $rule
     * @param Rule $replacement
     *
     * @return $this
     *
     * @throws OutOfBoundsException If the rule to replace is not found
     */
    public function replace(Rule $rule, Rule $replacement): static
    {
        $ruleAt = array_search($rule, $this->rules, true);
        if ($ruleAt === false) {
            throw new OutOfBoundsException('Rule to replace not found');
        }

        array_splice($this->rules, $ruleAt, 1, [$replacement]);

        return $this;
    }

    /**
     * Remove a rule from this chain
     *
     * @param Rule $rule
     *
     * @return $this
     */
    public function remove(Rule $rule): static
    {
        $ruleAt = array_search($rule, $this->rules, true);
        if ($ruleAt !== false) {
            array_splice($this->rules, $ruleAt, 1, []);
        }

        return $this;
    }

    /**
     * Get whether this chain has any rules
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->rules);
    }

    public function count(): int
    {
        return count($this->rules);
    }
}
