<?php

namespace ipl\Sql;

/**
 * Interface for database expressions that do need quoting or escaping, e.g. new Expression('NOW()');
 */
interface ExpressionInterface
{
    /**
     * Get the statement of the expression
     *
     * @return string
     */
    public function getStatement();

    /**
     * Get the columns used by the expression
     *
     * @return array
     */
    public function getColumns();

    /**
     * Set the columns to use by the expression
     *
     * @param array $columns
     *
     * @return $this
     */
    public function setColumns(array $columns);

    /**
     * Get the values for the expression
     *
     * @return array
     */
    public function getValues();
}
