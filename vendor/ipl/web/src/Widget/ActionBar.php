<?php

namespace ipl\Web\Widget;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Web\Common\BaseTarget;
use ipl\Web\Url;

/**
 * Action bar element for displaying a list of links
 */
class ActionBar extends BaseHtmlElement
{
    use BaseTarget;

    protected $contentSeparator = ' ';

    protected $defaultAttributes = [
        'class'            => 'action-bar',
        'data-base-target' => '_self'
    ];

    protected $tag = 'div';

    /**
     * Create a action bar
     *
     * @param Attributes|array $attributes
     */
    public function __construct($attributes = null)
    {
        $this->getAttributes()->add($attributes);
    }

    /**
     * Add a link to the action bar
     *
     * @param mixed      $content
     * @param Url|string $url
     * @param string     $icon
     *
     * @return $this
     */
    public function addLink($content, $url, $icon = null)
    {
        $this->add(new ActionLink($content, $url, $icon));

        return $this;
    }
}
