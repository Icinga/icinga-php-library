<?php

namespace ipl\Web\Layout;

use ipl\Html\BaseHtmlElement;

/**
 * Container for footer
 */
class Footer extends BaseHtmlElement
{
    protected $contentSeparator = "\n";

    protected $defaultAttributes = ['class' => 'footer'];

    protected $tag = 'div';
}
