<?php

namespace ipl\Web\Widget;

use ipl\Html\HtmlDocument;
use ipl\Html\ValidHtml;
use ipl\Web\Compat\StyleWithNonce;
use ipl\Web\Layout\ItemLayout;
use ipl\Web\Layout\ItemTableLayout;
use LogicException;

/**
 * ItemTable
 *
 * @template Item
 * @extends ItemList<Item>
 */
class ItemTable extends ItemList
{
    /** @var array<string, mixed> */
    protected $baseAttributes = [
        'class'            => 'item-table',
        'data-base-target' => '_next'
    ];

    /** @var bool Whether the list already listens for an item's assembly to determine the number of caption columns */
    protected $listeningForColumnCount = false;

    /** @var StyleWithNonce */
    protected $style;

    protected function init(): void
    {
        parent::init();

        $this->setItemLayoutClass(ItemTableLayout::class);
    }

    /**
     * Create a list item for the given data
     *
     * @param object $data
     *
     * @return ItemTableRow<Item>
     */
    protected function createListItem(object $data): ValidHtml
    {
        return new ItemTableRow($data, $this);
    }

    public function getItemLayout($item): ItemLayout
    {
        $layout = parent::getItemLayout($item);
        if (! $this->listeningForColumnCount) {
            $this->listeningForColumnCount = true;
            $layout->on(HtmlDocument::ON_ASSEMBLED, function (ItemTableLayout $layout) {
                $columns = $layout->getColumnCount();
                if ($columns === 0) {
                    throw new LogicException('An item table requires at least a single column.');
                }

                $this->style->addFor($this, ['--columns' => $columns]);
            });
        }

        return $layout;
    }

    protected function assemble(): void
    {
        parent::assemble();

        $this->style = new StyleWithNonce();
        $this->addHtml($this->style);
    }
}
