<?php

namespace ipl\Sql\Contract;

interface Quoter
{
    /**
     * Quote a string so that it can be safely used as table or column name, even if it is a reserved name
     *
     * The quote character depends on the underlying database adapter that is being used.
     *
     * @param string $identifier
     *
     * @return string
     */
    public function quoteIdentifier($identifier);
}
