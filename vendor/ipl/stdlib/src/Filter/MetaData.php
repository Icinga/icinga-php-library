<?php

namespace ipl\Stdlib\Filter;

use ipl\Stdlib\Data;

/**
 * Complement {@see MetaDataProvider} by lazily creating a {@see Data} bag on first access
 */
trait MetaData
{
    protected ?Data $metaData = null;

    public function metaData(): Data
    {
        return $this->metaData ??= new Data();
    }
}
