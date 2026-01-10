<?php

namespace ipl\Orm\Contract;

use ipl\Orm\Behavior;
use ipl\Orm\Model;

interface PersistBehavior extends Behavior
{
    /**
     * Apply this behavior on the given model
     *
     * Called when the model is persisted in the database.
     *
     * @param Model $model
     */
    public function persist(Model $model);
}
