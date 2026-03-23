<?php

namespace ipl\Sql;

/**
 * Interface for the ORDER BY part of a query
 */
interface OrderByInterface
{
    /**
     * Get whether a ORDER BY part is configured
     *
     * @return bool
     */
    public function hasOrderBy();

    /**
     * Get the ORDER BY part of the query
     *
     * @return array|null
     */
    public function getOrderBy();

    /**
     * Set the ORDER BY part of the query - either plain columns or expressions or scalar subqueries
     *
     * Note that this method does not override an already set ORDER BY part. Instead, each call to this function
     * appends the specified ORDER BY part to an already existing one.
     *
     * This method does NOT quote the columns you specify for the ORDER BY.
     * If you allow user input here, you must protected yourself against SQL injection using
     * {@link Connection::quoteIdentifier()} for the field names passed to this method.
     * If you are using special field names, e.g. reserved keywords for your DBMS, you are required to use
     * {@link Connection::quoteIdentifier()} as well.
     *
     * @param string|int|array $orderBy   The ORDER BY part. The items can be in any format of the following:
     *                                    ['column', 'column' => 'DESC', 'column' => SORT_DESC, ['column', 'DESC']]
     * @param string|int   $direction     The default direction. Can be any of the following:
     *                                    'ASC', 'DESC', SORT_ASC, SORT_DESC
     *
     * @return $this
     */
    public function orderBy($orderBy, $direction = null);

    /**
     * Reset the ORDER BY part of the query
     *
     * @return $this
     */
    public function resetOrderBy();
}
