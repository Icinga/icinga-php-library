<?php

namespace ipl\Web\Layout;

use ipl\Html\Attributes;
use ipl\Html\HtmlDocument;

class MinimalItemLayout extends ItemLayout
{
    public const NAME = 'minimal';

    public function createAttributes(Attributes $attributes): void
    {
        parent::createAttributes($attributes);

        $attributes->get('class')->addValue('minimal-item-layout');
    }

    protected function assembleMain(HtmlDocument $container): void
    {
        $this->registerHeader($container);
    }

    protected function assembleHeader(HtmlDocument $container): void
    {
        $this->registerTitle($container);
        $this->registerCaption($container);
        $this->registerExtendedInfo($container);
    }
}
