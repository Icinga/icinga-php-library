<?php

namespace ipl\Html\Contract;

interface ValueCandidates
{
    /**
     * Get value candidates of this element
     *
     * @return array<int, mixed>
     */
    public function getValueCandidates();

    /**
     * Set value candidates of this element
     *
     * @param array<int, mixed> $values
     *
     * @return $this
     */
    public function setValueCandidates(array $values);
}
