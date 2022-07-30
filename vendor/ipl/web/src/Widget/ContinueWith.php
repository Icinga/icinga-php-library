<?php

namespace ipl\Web\Widget;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
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

    /** @var string */
    protected $title;

    public function __construct(Url $url, $filter)
    {
        $this->url = $url;
        $this->filter = $filter;
    }

    /**
     * Set title for the anchor
     *
     * @param string $title
     *
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    public function assemble()
    {
        $filter = $this->filter;
        if (is_callable($filter)) {
            $filter = $filter(); /** @var Filter\Rule $filter */
        }

        if ($filter instanceof Filter\Chain && $filter->isEmpty()) {
            $this->addHtml(new HtmlElement(
                'span',
                Attributes::create(['class' => ['control-button', 'disabled']]),
                new Icon('share')
            ));
        } else {
            $this->addHtml(new ActionLink(
                null,
                $this->url->setQueryString(QueryString::render($filter)),
                'share',
                ['class' => 'control-button', 'title' => $this->title]
            ));
        }
    }
}
