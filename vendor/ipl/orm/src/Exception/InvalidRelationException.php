<?php

namespace ipl\Orm\Exception;

use Exception;
use ipl\Orm\Model;

class InvalidRelationException extends Exception
{
    /** @var string The relation name */
    protected string $relation;

    /** @var ?Model The target model */
    protected ?Model $model = null;

    /**
     * Create a new InvalidRelationException
     *
     * @param string $relation The relation name
     * @param ?Model $model The target model
     */
    public function __construct(string $relation, ?Model $model = null)
    {
        $this->relation = $relation;
        $this->model = $model;

        parent::__construct(sprintf(
            'Cannot join relation "%s"%s. Relation not found.',
            $relation,
            $model ? ' in model ' . get_class($model) : ''
        ));
    }

    /**
     * Get the relation name
     *
     * @return string
     */
    public function getRelation(): string
    {
        return $this->relation;
    }

    /**
     * Get the target model
     *
     * @return ?Model
     */
    public function getModel(): ?Model
    {
        return $this->model;
    }
}
