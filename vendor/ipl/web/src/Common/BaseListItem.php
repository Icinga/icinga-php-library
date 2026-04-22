<?php

namespace ipl\Web\Common;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Web\Widget\ItemList;

/**
 * Base class for list items
 *
 * @deprecated Use a {@see ItemList} with a dedicated {@see ItemRenderer} instead.
 */
abstract class BaseListItem extends BaseHtmlElement
{
    /** @var array<string, mixed> */
    protected $baseAttributes = ['class' => ['list-item', 'item-layout', 'default-item-layout']];

    /** @var object The associated list item */
    protected $item;

    /** @var BaseItemList The list where the item is part of */
    protected $list;

    protected $tag = 'li';

    /**
     * Create a new list item
     *
     * @param object       $item
     * @param BaseItemList $list
     */
    public function __construct($item, BaseItemList $list)
    {
        $this->item = $item;
        $this->list = $list;

        $this->addAttributes($this->baseAttributes);

        $this->init();
    }

    abstract protected function assembleHeader(BaseHtmlElement $header): void;

    abstract protected function assembleMain(BaseHtmlElement $main): void;

    protected function assembleFooter(BaseHtmlElement $footer): void
    {
    }

    protected function assembleCaption(BaseHtmlElement $caption): void
    {
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
    }

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
    }

    protected function createCaption(): BaseHtmlElement
    {
        $caption = new HtmlElement('section', Attributes::create(['class' => 'caption']));

        $this->assembleCaption($caption);

        return $caption;
    }

    protected function createHeader(): BaseHtmlElement
    {
        $header = new HtmlElement('header');

        $this->assembleHeader($header);

        return $header;
    }

    protected function createMain(): BaseHtmlElement
    {
        $main = new HtmlElement('div', Attributes::create(['class' => 'main']));

        $this->assembleMain($main);

        return $main;
    }

    protected function createFooter(): ?BaseHtmlElement
    {
        $footer = new HtmlElement('footer');

        $this->assembleFooter($footer);
        if ($footer->isEmpty()) {
            return null;
        }

        return $footer;
    }

    protected function createTimestamp(): ?BaseHtmlElement
    {
        return null;
    }

    protected function createTitle(): BaseHtmlElement
    {
        $title = new HtmlElement('div', Attributes::create(['class' => 'title']));

        $this->assembleTitle($title);

        return $title;
    }

    /**
     * @return ?BaseHtmlElement
     */
    protected function createVisual(): ?BaseHtmlElement
    {
        $visual = new HtmlElement('div', Attributes::create(['class' => 'visual']));

        $this->assembleVisual($visual);
        if ($visual->isEmpty()) {
            return null;
        }

        return $visual;
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
        $this->add([
            $this->createVisual(),
            $this->createMain()
        ]);
    }
}
