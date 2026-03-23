<?php

namespace ipl\Orm\Relation;

/**
 * One-to-one relationship with a junction table
 */
class BelongsToOne extends BelongsToMany
{
    protected const RELATION_CLASS = HasOne::class;

    protected $isOne = true;
}
