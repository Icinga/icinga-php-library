<?php

namespace ipl\Web\Widget;

use ipl\Html\BaseHtmlElement;

/**
 * List item
 *
 * It is rarely necessary to extend this class. Instead, you should create a new layout class that extends
 * {@see ItemLayout} and pass it to the {@see ItemList} instance.
 *
 * @template Item
 */
class ListItem extends BaseHtmlElement
{
    /** @var Item The associated list item */
    protected $item;

    /** @var ItemList<Item> The list where the item is part of */
    protected $list;

    protected $tag = 'li';

    protected $defaultAttributes = ['class' => 'list-item'];

    /**
     * Create a new list item
     *
     * @param Item $item
     * @param ItemList<Item> $list
     */
    public function __construct($item, ItemList $list)
    {
        $this->item = $item;
        $this->list = $list;
    }

    protected function assemble()
    {
        $layout = $this->list->getItemLayout($this->item);

        $this->addAttributes($layout->getAttributes());
        $this->addHtml($layout);
    }
}
