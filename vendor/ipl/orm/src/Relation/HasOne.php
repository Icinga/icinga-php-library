<?php

namespace ipl\Orm\Relation;

use ipl\Orm\Relation;

/**
 * One-to-one relationship
 */
class HasOne extends Relation
{
    protected ?string $reverseClass = BelongsTo::class;
}
