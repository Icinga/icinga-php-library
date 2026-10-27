<?php

namespace ipl\Web\Layout;

use ipl\Html\Attributes;
use ipl\Html\HtmlDocument;

class MinimalItemTableLayout extends ItemTableLayout
{
    public const NAME = 'minimal';

    protected function createAttributes(Attributes $attributes): void
    {
        parent::createAttributes($attributes);

        $attributes->get('class')->addValue('minimal-item-layout');
    }

    protected function assembleHeader(HtmlDocument $container): void
    {
        $this->registerTitle($container);
        $this->registerCaption($container);
    }

    protected function assembleMain(HtmlDocument $container): void
    {
        $this->registerHeader($container);
    }
}
