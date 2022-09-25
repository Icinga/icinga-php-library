<?php

namespace ipl\Web\Common;

/**
 * @method \ipl\Html\Attributes getAttributes()
 */
trait BaseTarget
{
    /**
     * Get the data-base-target attribute
     *
     * @return string|null
     */
    public function getBaseTarget()
    {
        return $this->getAttributes()->get('data-base-target')->getValue();
    }

    /**
     * Set the data-base-target attribute
     *
     * @param string $target
     *
     * @return $this
     */
    public function setBaseTarget($target)
    {
        $this->getAttributes()->set('data-base-target', $target);

        return $this;
    }
}
