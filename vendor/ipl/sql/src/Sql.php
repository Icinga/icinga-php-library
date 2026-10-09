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
    public const ALL = 'AND';

    /**
     * SQL OR operator
     */
    public const ANY = 'OR';

    /**
     * SQL AND NOT operator
     */
    public const NOT_ALL = 'AND NOT';

    /**
     * SQL OR NOT operator
     */
    public const NOT_ANY = 'OR NOT';

    /**
     * Create and return a DELETE statement
     *
     * @return Delete
     */
    public static function delete(): Delete
    {
        return new Delete();
    }

    /**
     * Create and return an INSERT statement
     *
     * @return Insert
     */
    public static function insert(): Insert
    {
        return new Insert();
    }

    /**
     * Create and return a SELECT statement
     *
     * @return Select
     */
    public static function select(): Select
    {
        return new Select();
    }

    /**
     * Create and return a UPDATE statement
     *
     * @return Update
     */
    public static function update(): Update
    {
        return new Update();
    }
}
