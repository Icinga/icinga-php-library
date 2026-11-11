<?php

namespace ipl\Stdlib\Filter;

use ipl\Stdlib\Data;

/**
 * Provide access to arbitrary rule meta data
 */
interface MetaDataProvider
{
    /**
     * Get this rule's metadata
     *
     * Implementations must return the same bag on every call, creating it on
     * first access or upfront.
     *
     * @return Data
     */
    public function metaData(): Data;
}
