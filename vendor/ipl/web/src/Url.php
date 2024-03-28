<?php

namespace ipl\Web;

use Icinga\Web\UrlParams;
use ipl\Stdlib\Filter\Rule;
use ipl\Web\Filter\QueryString;

/**
 * @TODO(el): Don't depend on Icinga Web's Url
 */
class Url extends \Icinga\Web\Url
{
    /** @var ?Rule */
    private $filter;

    /**
     * Set the filter
     *
     * @param ?Rule $filter
     *
     * @return $this
     */
    public function setFilter(?Rule $filter): self
    {
        $this->filter = $filter;

        return $this;
    }

    /**
     * Get the filter
     *
     * @return ?Rule
     */
    public function getFilter(): ?Rule
    {
        return $this->filter;
    }

    /**
     * Render and return the filter and parameters as query string
     *
     * @param ?string $separator
     *
     * @return string
     */
    public function getQueryString($separator = null)
    {
        if ($this->filter === null) {
            return parent::getQueryString($separator);
        }

        $params = UrlParams::fromQueryString(QueryString::render($this->filter));
        foreach ($this->getParams()->toArray(false) as $name => $value) {
            if (is_int($name)) {
                $name = $value;
                $value = true;
            }

            $params->addEncoded($name, $value);
        }

        return $params->toString($separator);
    }

    public function __toString()
    {
        return $this->getAbsoluteUrl('&');
    }
}
