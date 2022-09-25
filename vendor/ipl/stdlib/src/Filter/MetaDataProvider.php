<?php

namespace ipl\Stdlib\Filter;

use ipl\Stdlib\Data;

interface MetaDataProvider
{
    /**
     * Get this rule's meta data
     *
     * @return Data
     */
    public function metaData();
}
