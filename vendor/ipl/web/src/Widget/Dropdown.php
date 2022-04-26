<?php

namespace ipl\Web\Widget;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Web\Url;

/**
 * Toggleable overlay dropdown element for displaying a list of links
 */
class Dropdown extends BaseHtmlElement
{
    /** @var array */
    protected $links = [];

    protected $defaultAttributes = ['class' => 'dropdown'];

    protected $tag = 'div';

    /**
     * Create a dropdown element
     *
     * @param mixed            $content
     * @param Attributes|array $attributes
     */
    public function __construct($content, $attributes = null)
    {
        $toggle = new ActionLink($content, '#', null, [
            'aria-expanded' => false,
            'aria-haspopup' => true,
            'class'         => 'dropdown-toggle',
            'role'          => 'button'
        ]);

        $this
            ->setContent($toggle)
            ->getAttributes()
                ->add($attributes);
    }

    /**
     * Add a link to the dropdown
     *
     * @param mixed      $content
     * @param Url|string $url
     * @param string     $icon
     *
     * @return $this
     */
    public function addLink($content, $url, $icon = null)
    {
        $this->links[] = new ActionLink($content, $url, $icon, ['class' => 'dropdown-item']);

        return $this;
    }

    protected function assemble()
    {
        $this->add(Html::tag('div', ['class' => 'dropdown-menu'], $this->links));
    }
}
