<?php

namespace ipl\Web\Layout;

use ipl\Html\Attributes;

class HeaderItemLayout extends MinimalItemLayout
{
    public const NAME = 'header';

    public function createAttributes(Attributes $attributes): void
    {
        parent::createAttributes($attributes);

        $attributes->get('class')->addValue('header-item-layout');
    }
}
