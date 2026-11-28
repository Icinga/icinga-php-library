<?php

namespace ipl\Web\Layout;

use ipl\Html\Attributes;

class DetailedItemTableLayout extends ItemTableLayout
{
    public const NAME = 'detailed';

    protected function createAttributes(Attributes $attributes): void
    {
        parent::createAttributes($attributes);

        $attributes->get('class')
            ->removeValue('default-item-layout')
            ->addValue('detailed-item-layout');
    }
}
