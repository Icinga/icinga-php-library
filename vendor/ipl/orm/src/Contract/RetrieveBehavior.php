<?php

namespace ipl\Orm\Contract;

use ipl\Orm\Behavior;
use ipl\Orm\Model;

interface RetrieveBehavior extends Behavior
{
    /**
     * Apply this behavior on the given model
     *
     * Called when the model is fetched from the database.
     *
     * @param Model $model
     */
    public function retrieve(Model $model);
}
