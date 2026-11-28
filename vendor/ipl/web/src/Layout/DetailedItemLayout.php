<?php

namespace ipl\Web\Layout;

use ipl\Html\Attributes;
use ipl\Html\HtmlDocument;

class DetailedItemLayout extends ItemLayout
{
    public const NAME = 'detailed';

    public function createAttributes(Attributes $attributes): void
    {
        parent::createAttributes($attributes);

        $attributes->get('class')
            ->removeValue('default-item-layout')
            ->addValue('detailed-item-layout');
    }

    protected function assembleMain(HtmlDocument $container): void
    {
        $this->registerHeader($container);
        $this->registerCaption($container);
        $this->registerFooter($container);
    }
}
