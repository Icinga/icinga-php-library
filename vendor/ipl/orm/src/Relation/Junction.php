<?php

namespace ipl\Orm\Relation;

use ipl\Orm\Model;

/**
 * Junction model for many-to-many relations
 */
class Junction extends Model
{
    /** @var string */
    protected $tableName;

    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * Set the table name
     *
     * @param string $tableName
     *
     * @return $this
     */
    public function setTableName($tableName)
    {
        $this->tableName = $tableName;

        return $this;
    }

    public function getKeyName()
    {
        return null;
    }

    public function getColumns()
    {
        return null;
    }
}
