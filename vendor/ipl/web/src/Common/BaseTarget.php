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
    public function getBaseTarget(): ?string
    {
        /** @var ?string $baseTarget */
        $baseTarget = $this->getAttributes()->get('data-base-target')->getValue();

        return $baseTarget;
    }

    /**
     * Set the data-base-target attribute
     *
     * @param string $target
     *
     * @return $this
     */
    public function setBaseTarget(string $target): self
    {
        $this->getAttributes()->set('data-base-target', $target);

        return $this;
    }
}
