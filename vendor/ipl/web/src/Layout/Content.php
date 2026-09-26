<?php

namespace ipl\Web\Layout;

use ipl\Html\BaseHtmlElement;

/**
 * Container for content
 */
class Content extends BaseHtmlElement
{
    protected $contentSeparator = "\n";

    protected $defaultAttributes = ['class' => 'content'];

    protected $tag = 'div';
}
