<?php

namespace ipl\Web\Layout;

use ipl\Html\Attributes;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\ValidHtml;
use ipl\Web\Widget\ItemTable\ItemTableRenderer;

/**
 * Item table layout
 *
 * @template Item
 * @extends ItemLayout<Item>
 */
class ItemTableLayout extends ItemLayout
{
    /** @var ItemTableRenderer */
    protected $renderer;

    /** @var int Number of caption columns */
    protected $columnCount = 0;

    /**
     * Create a new item table layout
     *
     * @param Item $item
     * @param ItemTableRenderer $renderer
     */
    public function __construct($item, ItemTableRenderer $renderer)
    {
        parent::__construct($item, $renderer);
    }

    /**
     * Get the number of caption columns
     *
     * @return int
     */
    public function getColumnCount(): int
    {
        return $this->columnCount;
    }

    /**
     * Create column
     *
     * @param string $class CSS class to apply
     *
     * @return HtmlElement
     */
    protected function createColumn(string $class): HtmlElement
    {
        return new HtmlElement('div', Attributes::create(['class' => ['col', $class]]));
    }

    protected function createVisual(): HtmlDocument
    {
        return $this->createColumn(ItemLayout::VISUAL);
    }

    protected function createMain(): HtmlDocument
    {
        return $this->createColumn(ItemLayout::MAIN);
    }

    protected function assembleHeader(HtmlDocument $container): void
    {
        $this->registerTitle($container);
    }

    /**
     * Create a container for the item's columns
     *
     * @return HtmlDocument
     */
    protected function createColumns(): HtmlDocument
    {
        return new HtmlDocument();
    }

    /**
     * Assemble columns for the item
     *
     * @param HtmlDocument $container
     *
     * @return void
     */
    protected function assembleColumns(HtmlDocument $container): void
    {
        $this->renderer->assembleColumns($this->item, $container, $this->getName());
    }

    /**
     * Create, assemble and register columns for the item
     *
     * @param HtmlDocument $container
     *
     * @return void
     */
    protected function registerColumns(HtmlDocument $container): void
    {
        $columns = $this->createColumns();

        $this->createBefore('columns', $container);

        $this->assembleColumns($columns);

        $this->columnCount = $columns->count();

        $container->addFrom($columns, function (ValidHtml $content) {
            return $this->createColumn('')->addHtml($content);
        });

        $this->createAfter('columns', $container);
    }

    protected function assemble(): void
    {
        $this->registerVisual($this);
        $this->registerMain($this);
        $this->registerColumns($this);
    }
}
