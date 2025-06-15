<?php

namespace ipl\Stdlib\Contract;

use ipl\Stdlib\Filter;

interface Filterable
{
    /**
     * Get the filter of the query
     *
     * @return Filter\Chain
     */
    public function getFilter();

    /**
     * Add a filter to the query
     *
     * Note that this method does not override an already set filter. Instead, multiple calls to this function add
     * the specified filter using a {@see Filter\All} chain.
     *
     * @param Filter\Rule $filter
     *
     * @return $this
     */
    public function filter(Filter\Rule $filter);

    /**
     * Add a filter to the query
     *
     * Note that this method does not override an already set filter. Instead, multiple calls to this function add
     * the specified filter using a {@see Filter\Any} chain.
     *
     * @param Filter\Rule $filter
     *
     * @return $this
     */
    public function orFilter(Filter\Rule $filter);

    /**
     * Add a filter to the query
     *
     * Note that this method does not override an already set filter. Instead, multiple calls to this function add
     * the specified filter wrapped by a {@see Filter\None} chain and using a {@see Filter\All} chain.
     *
     * @param Filter\Rule $filter
     *
     * @return $this
     */
    public function notFilter(Filter\Rule $filter);

    /**
     * Add a filter to the query
     *
     * Note that this method does not override an already set filter. Instead, multiple calls to this function add
     * the specified filter wrapped by a {@see Filter\None} chain and using a {@see Filter\Any} chain.
     *
     * @param Filter\Rule $filter
     *
     * @return $this
     */
    public function orNotFilter(Filter\Rule $filter);
}
