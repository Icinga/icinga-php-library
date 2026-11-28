<?php

namespace ipl\Orm\Exception;

use Exception;
use ipl\Orm\Model;

class InvalidColumnException extends Exception
{
    /** @var string The column name */
    protected string $column;

    /** @var Model The target model */
    protected Model $model;

    /**
     * Create a new InvalidColumnException
     *
     * @param string $column The column name
     * @param Model $model The target model
     */
    public function __construct(string $column, Model $model)
    {
        $this->column = $column;
        $this->model = $model;

        parent::__construct(sprintf(
            "Can't require column '%s' in model '%s'. Column not found.",
            $column,
            get_class($model)
        ));
    }

    /**
     * Get the column name
     *
     * @return string
     */
    public function getColumn(): string
    {
        return $this->column;
    }

    /**
     * Get the target model
     *
     * @return Model
     */
    public function getModel(): Model
    {
        return $this->model;
    }
}
