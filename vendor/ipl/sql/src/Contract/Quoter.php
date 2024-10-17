<?php

namespace ipl\Sql\Contract;

interface Quoter
{
    /**
     * Quote an identifier so that it can be safely used as table or column name, even if it is a reserved name
     *
     * If a string is passed that contains dots, the parts separated by them are quoted individually.
     * (e.g. `myschema.mytable` turns into `"myschema"."mytable"`) If an array is passed, the entries
     * are quoted as-is. (e.g. `[myschema.my, table]` turns into `"myschema.my"."table"`)
     *
     * The quote character depends on the underlying database adapter that is being used.
     *
     * @param string|string[] $identifiers
     *
     * @return string
     */
    public function quoteIdentifier($identifiers);
}
