<?php

namespace ipl\Orm;

use ipl\Sql\Select;

class UnionQuery extends Query
{
    /** @var Query[] Underlying queries */
    private $unions;

    /**
     * Get the underlying queries
     *
     * @return Query[]
     */
    public function getUnions()
    {
        if ($this->unions === null) {
            $this->unions = [];

            foreach ($this->getModel()->getUnions() as list($target, $relations, $columns)) {
                $query = (new Query())
                    ->setDb($this->getDb())
                    ->setModel(new $target())
                    ->columns($columns)
                    ->disableDefaultSort()
                    ->with($relations);

                $this->unions[] = $query;
            }
        }

        return $this->unions;
    }

    public function getSelectBase()
    {
        if ($this->selectBase === null) {
            $this->selectBase = new Select();
        }

        $union = new Select();

        foreach ($this->getUnions() as $query) {
            $select = $query->assembleSelect();
            $columns = $select->getColumns();
            $select->resetColumns();
            ksort($columns);
            $select->columns($columns);

            $union->unionAll($select);
        }

        $this->selectBase->from([$this->getModel()->getTableName() => $union]);

        return $this->selectBase;
    }
}
