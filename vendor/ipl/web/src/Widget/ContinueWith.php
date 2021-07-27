<?php

namespace ipl\Web\Widget;

use ipl\Html\BaseHtmlElement;
use ipl\Stdlib\Filter;
use ipl\Web\Common\BaseTarget;
use ipl\Web\Filter\QueryString;
use ipl\Web\Url;

class ContinueWith extends BaseHtmlElement
{
    use BaseTarget;

    protected $tag = 'span';

    protected $defaultAttributes = ['class' => 'continue-with'];

    /** @var Url */
    protected $url;

    /** @var Filter\Rule|callable */
    protected $filter;

    public function __construct(Url $url, $filter)
    {
        $this->url = $url;
        $this->filter = $filter;
    }

    public function assemble()
    {
        $filter = $this->filter;
        if (is_callable($filter)) {
            $filter = $filter(); /** @var Filter\Rule $filter */
        }

        if ($filter instanceof Filter\Chain && $filter->isEmpty()) {
            $this->add(new Icon('share'));
        } else {
            $this->add(new ActionLink(
                null,
                $this->url->setQueryString(QueryString::render($filter)),
                'share'
            ));
        }
    }
}
