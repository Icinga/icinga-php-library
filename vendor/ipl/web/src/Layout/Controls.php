<?php

namespace ipl\Web\Layout;

use ipl\Html\BaseHtmlElement;
use ipl\Web\Widget\Tabs;

/**
 * Container for controls
 */
class Controls extends BaseHtmlElement
{
    /** @var Tabs */
    protected $tabs;

    protected $contentSeparator = "\n";

    protected $defaultAttributes = ['class' => 'controls'];

    protected $tag = 'div';

    /**
     * Get the tabs
     *
     * @return Tabs
     */
    public function getTabs()
    {
        return $this->tabs;
    }

    /**
     * Set the tabs
     *
     * @param Tabs $tabs
     *
     * @return $this
     */
    public function setTabs(Tabs $tabs)
    {
        $this->tabs = $tabs;

        return $this;
    }

    public function isEmpty()
    {
        if (! parent::isEmpty()) {
            return false;
        }

        return $this->tabs->count() === 0;
    }

    protected function assemble()
    {
        $this->prepend($this->getTabs());
    }
}
