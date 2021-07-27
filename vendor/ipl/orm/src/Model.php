<?php

namespace ipl\Orm;

use ipl\Sql\Connection;
use ipl\Stdlib\Properties;

/**
 * Models represent single database tables or parts of it.
 * They are also used to interact with the tables, i.e. in order to query for data.
 */
abstract class Model implements \ArrayAccess
{
    use Properties;

    final public function __construct(array $properties = null)
    {
        if (! empty($this->properties)) {
            $this->setProperties($properties);
        }

        $this->init();
    }

    /**
     * Get the related database table's name
     *
     * @return string
     */
    abstract public function getTableName();

    /**
     * Get the column name(s) of the primary key
     *
     * @return string|array Array if the primary key is compound, string otherwise
     */
    abstract public function getKeyName();

    /**
     * Get the model's queryable columns
     *
     * @return array
     */
    abstract public function getColumns();

    /**
     * Get the model's column meta data
     *
     * Meta data is indexed by column names, values are either strings (labels) or arrays of this format:
     *
     * [
     *  'label' => 'A Column',
     *  'type'  => 'enum(y,n)'
     * ]
     *
     * @return array
     */
    public function getMetaData()
    {
        return [];
    }

    /**
     * Get a query which is tied to this model and the given database connection
     *
     * @param Connection $db
     *
     * @return Query
     */
    public static function on(Connection $db)
    {
        return (new Query())
            ->setDb($db)
            ->setModel(new static());
    }

    /**
     * Get the models aggregate columns
     *
     * @return array|bool Aggregate columns as array or true to always aggregate
     */
    public function getAggregateColumns()
    {
        return false;
    }

    /**
     * Get the model's default sort
     *
     * @return array|string
     */
    public function getDefaultSort()
    {
        return [];
    }

    /**
     * Get the model's search columns
     *
     * @return array
     */
    public function getSearchColumns()
    {
        return [];
    }

    /**
     * Create the model's behaviors
     *
     * @param Behaviors $behaviors
     */
    public function createBehaviors(Behaviors $behaviors)
    {
    }

    /**
     * Create the model's relations
     *
     * If your model should be associated to other models, override this method and create the model's relations.
     */
    public function createRelations(Relations $relations)
    {
    }

    /**
     * Initialize the model
     *
     * If you want to adjust the model after construction, override this method.
     */
    protected function init()
    {
    }
}
