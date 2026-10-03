<?php

namespace ipl\Stdlib\Filter;

/**
 * Match when the column value is similar to the filter value, supporting `*` wildcards
 */
class Like extends Condition
{
    protected bool $ignoreCase = false;

    /**
     * Ignore case on both sides of the equation
     *
     * @return $this
     */
    public function ignoreCase(): static
    {
        $this->ignoreCase = true;

        return $this;
    }

    /**
     * Return whether this rule ignores case
     *
     * @return bool
     */
    public function ignoresCase(): bool
    {
        return $this->ignoreCase;
    }
}
