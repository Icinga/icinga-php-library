<?php

namespace ipl\Web;

/**
 * @TODO(el): Don't depend on Icinga Web's Url
 */
class Url extends \Icinga\Web\Url
{
    public function __toString()
    {
        return $this->getAbsoluteUrl('&');
    }
}
