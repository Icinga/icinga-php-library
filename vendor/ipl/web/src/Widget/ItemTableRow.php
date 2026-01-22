<?php

namespace ipl\Web\Widget;

use ipl\Html\BaseHtmlElement;

/**
 * Table row item
 *
 * It is rarely necessary to extend this class. Instead, you should create a new layout class that extends
 * {@see ItemLayout} and pass it to the {@see ItemTable} instance.
 *
 * @template Item
 */
class ItemTableRow extends BaseHtmlElement
{
    /** @var Item The associated list item */
    protected $item;

    /** @var ItemList<Item> The list where the item is part of */
    protected $list;

    protected $tag = 'li';

    protected $defaultAttributes = ['class' => 'table-row'];

    /**
     * Create a new table row item
     *
     * @param Item $item
     * @param ItemTable $table
     */
    public function __construct($item, ItemTable $table)
    {
        $this->item = $item;
        $this->list = $table;
    }

    protected function assemble()
    {
        $layout = $this->list->getItemLayout($this->item);

        $this->addAttributes($layout->getAttributes());
        $this->addHtml($layout);
    }
}
