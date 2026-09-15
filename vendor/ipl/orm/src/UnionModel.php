<?php

namespace ipl\Orm;

use ipl\Sql\Connection;

abstract class UnionModel extends Model
{
    /**
     * Get a UNION query which is tied to this model and the given database connection
     *
     * @param Connection $db
     *
     * @return UnionQuery
     */
    public static function on(Connection $db)
    {
        return (new UnionQuery())
            ->setDb($db)
            ->setModel(new static());
    }

    /**
     * Get the UNION models and columns
     *
     * @return array
     */
    abstract public function getUnions();
}
