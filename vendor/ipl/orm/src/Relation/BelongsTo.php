<?php

namespace ipl\Orm\Relation;

use ipl\Orm\Relation;

/**
 * Inverse of a one-to-one or one-to-many relationship
 */
class BelongsTo extends Relation
{
    protected $inverse = true;
}
