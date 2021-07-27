<?php

namespace ipl\Stdlib\Filter;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use OutOfBoundsException;

abstract class Chain implements Rule, MetaDataProvider, IteratorAggregate, Countable
{
    use MetaData;

    /** @var Rule[] */
    protected $rules = [];

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
     * Get an iterator this chain's rules
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->rules);
    }

    /**
     * Add a rule to this chain
     *
     * @param Rule $rule
     *
     * @return $this
     */
    public function add(Rule $rule)
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
     * @throws OutOfBoundsException In case no existing rule is found
     * @return $this
     */
    public function insertBefore(Rule $rule, Rule $before)
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
     * @throws OutOfBoundsException In case no existing rule is found
     * @return $this
     */
    public function insertAfter(Rule $rule, Rule $after)
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
    public function has(Rule $rule)
    {
        return array_search($rule, $this->rules, true) !== false;
    }

    /**
     * Replace a rule with another one in this chain
     *
     * @param Rule $rule
     * @param Rule $replacement
     *
     * @throws OutOfBoundsException In case no existing rule is found
     * @return $this
     */
    public function replace(Rule $rule, Rule $replacement)
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
    public function remove(Rule $rule)
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
    public function isEmpty()
    {
        return empty($this->rules);
    }

    /**
     * Count this chain's rules
     *
     * @return int
     */
    public function count()
    {
        return count($this->rules);
    }
}
