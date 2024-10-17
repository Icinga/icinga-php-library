<?php

namespace ipl\Stdlib;

trait Filters
{
    /** @var Filter\Chain */
    protected $filter;

    public function getFilter()
    {
        return $this->filter ?: Filter::all();
    }

    public function filter(Filter\Rule $filter)
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

    public function orFilter(Filter\Rule $filter)
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

    public function notFilter(Filter\Rule $filter)
    {
        $this->filter(Filter::none($filter));

        return $this;
    }

    public function orNotFilter(Filter\Rule $filter)
    {
        $this->orFilter(Filter::none($filter));

        return $this;
    }
}
