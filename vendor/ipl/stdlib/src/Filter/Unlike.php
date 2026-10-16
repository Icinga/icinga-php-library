<?php

namespace ipl\Stdlib\Filter;

/**
 * Match when the column value does not match the filter value, supporting `*` wildcards
 */
class Unlike extends Condition
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
