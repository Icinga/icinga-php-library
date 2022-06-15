<?php

namespace ipl\Sql;

/**
 * The SQL helper provides a set of static methods for quoting and escaping identifiers to make their use safe in SQL
 * queries or fragments
 */
class Sql
{
    /**
     * SQL AND operator
     */
    const ALL = 'AND';

    /**
     * SQL OR operator
     */
    const ANY = 'OR';

    /**
     * SQL AND NOT operator
     */
    const NOT_ALL = 'AND NOT';

    /**
     * SQL OR NOT operator
     */
    const NOT_ANY = 'OR NOT';

    /**
     * Create and return a DELETE statement
     *
     * @return Delete
     */
    public static function delete()
    {
        return new Delete();
    }

    /**
     * Create and return a INSERT statement
     *
     * @return Insert
     */
    public static function insert()
    {
        return new Insert();
    }

    /**
     * Create and return a SELECT statement
     *
     * @return Select
     */
    public static function select()
    {
        return new Select();
    }

    /**
     * Create and return a UPDATE statement
     *
     * @return Update
     */
    public static function update()
    {
        return new Update();
    }
}
