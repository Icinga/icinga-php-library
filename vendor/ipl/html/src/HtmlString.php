<?php

namespace ipl\Html;

/**
 * HTML string
 *
 * HTML strings promise to be already escaped and can be anything from simple text to full HTML markup.
 */
class HtmlString extends Text
{
    protected $escaped = true;
}
