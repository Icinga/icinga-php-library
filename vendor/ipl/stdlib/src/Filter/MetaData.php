<?php

namespace ipl\Stdlib\Filter;

use ipl\Stdlib\Data;

trait MetaData
{
    /** @var Data */
    protected $metaData;

    public function metaData()
    {
        if ($this->metaData === null) {
            $this->metaData = new Data();
        }

        return $this->metaData;
    }
}
