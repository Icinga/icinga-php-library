<?php

namespace ipl\Stdlib;

/**
 * Add and compose filter rules on a query or collection
 */
trait Filters
{
    protected ?Filter\Chain $filter = null;

    public function getFilter(): Filter\Chain
    {
        return $this->filter ?: Filter::all();
    }

    public function filter(Filter\Rule $filter): static
    {
        $currentFilter = $this->getFilter();
        if ($currentFilter instanceof Filter\All) {
            $this->filter = $currentFilter->add($filter);
        } else {
            $this->filter = Filter::all($filter);
            if (! $currentFilter->isEmpty()) {
                $this->filter->insertBefore($currentFilter, $filter);
            }
        }

        return $this;
    }

    public function orFilter(Filter\Rule $filter): static
    {
        $currentFilter = $this->getFilter();
        if ($currentFilter instanceof Filter\Any) {
            $this->filter = $currentFilter->add($filter);
        } else {
            $this->filter = Filter::any($filter);
            if (! $currentFilter->isEmpty()) {
                $this->filter->insertBefore($currentFilter, $filter);
            }
        }

        return $this;
    }

    public function notFilter(Filter\Rule $filter): static
    {
        $this->filter(Filter::none($filter));

        return $this;
    }

    public function orNotFilter(Filter\Rule $filter): static
    {
        $this->orFilter(Filter::none($filter));

        return $this;
    }
}
