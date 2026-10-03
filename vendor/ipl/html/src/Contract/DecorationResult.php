<?php

namespace ipl\Html\Contract;

use ipl\Html\ValidHtml;

interface DecorationResult
{
    /**
     * Add the given HTML to the end of the result
     *
     * @param ValidHtml $html The HTML to add
     *
     * @return $this
     */
    public function append(ValidHtml $html): static;

    /**
     * Prepend the given HTML to the beginning of the result
     *
     * @param ValidHtml $html The HTML to prepend
     *
     * @return $this
     */
    public function prepend(ValidHtml $html): static;

    /**
     * Set the given HTML as the container of the result
     *
     * @param MutableHtml $html The container
     *
     * @return $this
     */
    public function wrap(MutableHtml $html): static;
}
