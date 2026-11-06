<?php

namespace ipl\Html\Contract;

use ipl\Html\ValidHtml;

/**
 * Representation of wrappable elements
 */
interface Wrappable extends ValidHtml
{
    /**
     * Get the wrapper, if any
     *
     * @return Wrappable|null
     */
    public function getWrapper();

    /**
     * Set the wrapper
     *
     * @param Wrappable $wrapper
     *
     * @return $this
     */
    public function setWrapper(Wrappable $wrapper);

    /**
     * Add a wrapper
     *
     * @param Wrappable $wrapper
     *
     * @return $this
     */
    public function addWrapper(Wrappable $wrapper);

    /**
     * Prepend a wrapper
     *
     * @param Wrappable $wrapper
     *
     * @return $this
     */
    public function prependWrapper(Wrappable $wrapper);
}
