<?php

namespace ipl\Web\Common;

use ipl\Web\Widget\EmptyStateBar;

/**
 * @method BaseOrderedListItem getItemClass()
 */
abstract class BaseOrderedItemList extends BaseItemList
{
    protected $tag = 'ol';

    protected function assemble(): void
    {
        $itemClass = $this->getItemClass();

        $i = 0;
        foreach ($this->data as $data) {
            $item = new $itemClass($data, $this);
            $item->setOrder($i++);

            $this->addHtml($item);
        }

        if ($this->isEmpty()) {
            $this->setTag('div');
            $this->addHtml(new EmptyStateBar(t('No items found.')));
        }
    }
}
