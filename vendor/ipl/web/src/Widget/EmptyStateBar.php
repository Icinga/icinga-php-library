<?php

namespace ipl\Web\Widget;

use ipl\Html\BaseHtmlElement;

class EmptyStateBar extends BaseHtmlElement
{
    /** @var mixed Content */
    protected $content;

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'empty-state-bar'];

    /**
     * Create an empty list
     *
     * @param mixed $content
     */
    public function __construct($content)
    {
        $this->content = $content;
    }

    protected function assemble(): void
    {
        $this->add($this->content);
    }
}
