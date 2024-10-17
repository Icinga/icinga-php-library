<?php

namespace ipl\Stdlib\Filter;

class Unequal extends Condition
{
    /** @var bool */
    protected $ignoreCase = false;

    /**
     * Ignore case on both sides of the equation
     *
     * @return $this
     */
    public function ignoreCase()
    {
        $this->ignoreCase = true;

        return $this;
    }

    /**
    * Return whether this rule ignores case
    *
    * @return bool
    */
    public function ignoresCase()
    {
        return $this->ignoreCase;
    }
}
