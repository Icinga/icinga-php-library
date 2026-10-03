<?php

namespace ipl\Orm\Relation;

use ipl\Orm\Relation;

/**
 * One-to-many relationship
 */
class HasMany extends Relation
{
    protected bool $isOne = false;
}
