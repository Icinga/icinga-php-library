<?php

namespace ipl\Web\Widget;

use ipl\Html\BaseHtmlElement;

class EmptyState extends BaseHtmlElement
{
    /** @var mixed Content */
    protected $content;

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'empty-state'];

    /**
     * Create an empty state
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
