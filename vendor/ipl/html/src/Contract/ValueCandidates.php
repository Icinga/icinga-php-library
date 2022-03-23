<?php

namespace ipl\Html\Contract;

interface ValueCandidates
{
    /**
     * Get value candidates of this element
     *
     * @return array
     */
    public function getValueCandidates();

    /**
     * Set value candidates of this element
     *
     * @param array $values
     *
     * @return $this
     */
    public function setValueCandidates(array $values);
}
