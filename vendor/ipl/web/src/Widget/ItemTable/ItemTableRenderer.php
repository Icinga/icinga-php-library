<?php

namespace ipl\Web\Widget\ItemTable;

use ipl\Html\HtmlDocument;
use ipl\Web\Common\ItemRenderer;

/**
 * Interface for item table renderers
 *
 * @template Item
 * @extends ItemRenderer<Item>
 */
interface ItemTableRenderer extends ItemRenderer
{
    /**
     * Assemble columns for an item
     *
     * @param Item $item The item to render
     * @param HtmlDocument $columns The container to add the result to
     * @param string $layout The name of the layout
     *
     * @return void
     */
    public function assembleColumns($item, HtmlDocument $columns, string $layout): void;
}
