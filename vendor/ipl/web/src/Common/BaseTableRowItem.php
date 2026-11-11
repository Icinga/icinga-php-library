<?php

namespace ipl\Web\Common;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Web\Widget\ItemTable;

/** @deprecated Use a {@see ItemTable} with a dedicated {@see ItemRenderer} instead. */
abstract class BaseTableRowItem extends BaseHtmlElement
{
    /** @var array<string, mixed> */
    protected $baseAttributes = ['class' => ['table-row', 'item-layout', 'default-item-layout']];

    /** @var object The associated list item */
    protected $item;

    /** @var ?BaseItemTable The list where the item is part of */
    protected $table;

    protected $tag = 'li';

    /**
     * Create a new table row item
     *
     * @param object $item
     * @param BaseItemTable|null $table
     */
    public function __construct($item, ?BaseItemTable $table = null)
    {
        $this->item = $item;
        $this->table = $table;

        if ($table === null) {
            $this->setTag('div');
        }

        $this->addAttributes($this->baseAttributes);

        $this->init();
    }

    abstract protected function assembleTitle(BaseHtmlElement $title): void;

    protected function assembleColumns(HtmlDocument $columns): void
    {
    }

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
    }

    /**
     * Create column
     *
     * @param mixed $content
     *
     * @return BaseHtmlElement
     */
    protected function createColumn($content = null): BaseHtmlElement
    {
        return new HtmlElement(
            'div',
            Attributes::create(['class' => 'col']),
            new HtmlElement(
                'div',
                Attributes::create(['class' => 'column-content']),
                ...Html::wantHtmlList($content)
            )
        );
    }

    protected function createColumns(): HtmlDocument
    {
        $columns = new HtmlDocument();

        $this->assembleColumns($columns);

        return $columns;
    }

    protected function createTitle(): BaseHtmlElement
    {
        $title = $this->createColumn()->addAttributes(['class' => 'title']);

        $this->assembleTitle($title->getFirst('div'));

        $title->prepend($this->createVisual());

        return $title;
    }

    protected function createVisual(): ?BaseHtmlElement
    {
        $visual = new HtmlElement('div', Attributes::create(['class' => 'visual']));

        $this->assembleVisual($visual);

        return $visual->isEmpty() ? null : $visual;
    }

    /**
     * Initialize the list item
     *
     * If you want to adjust the list item after construction, override this method.
     */
    protected function init(): void
    {
    }

    protected function assemble(): void
    {
        $this->addHtml(
            $this->createTitle(),
            $this->createColumns()
        );
    }
}
