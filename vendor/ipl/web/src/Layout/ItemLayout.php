<?php

namespace ipl\Web\Layout;

use ipl\Html\Attributes;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Web\Common\ItemRenderer;

/**
 * Layout for items
 *
 * @phpstan-type _SECTION1 = static::VISUAL|static::MAIN|static::HEADER|static::FOOTER
 * @phpstan-type SECTION = _SECTION1|static::CAPTION|static::TITLE|static::EXTENDED_INFO
 * @template Item
 */
class ItemLayout extends HtmlDocument
{
    /** The name of this layout */
    public const NAME = 'common';

    /** Identifier for the section containing an item's visual representation */
    public const VISUAL = 'visual';

    /** Identifier for the section containing an item's main content */
    public const MAIN = 'main';

    /** Identifier for the section containing an item's header */
    public const HEADER = 'header';

    /** Identifier for the section containing an item's footer */
    public const FOOTER = 'footer';

    /** Identifier for the section containing an item's caption */
    public const CAPTION = 'caption';

    /** Identifier for the section containing an item's title */
    public const TITLE = 'title';

    /** Identifier for the section containing an item's extended information */
    public const EXTENDED_INFO = 'extended-info';

    /** @var Item */
    protected $item;

    /** @var ItemRenderer */
    protected $renderer;

    /** @var array<SECTION, string> */
    protected $before = [];

    /** @var array<SECTION, string> */
    protected $after = [];

    /**
     * Create a new layout for items
     *
     * @param Item $item The item to render
     * @param ItemRenderer<Item> $renderer The renderer used to assemble the layout's content
     */
    public function __construct($item, ItemRenderer $renderer)
    {
        $this->item = $item;
        $this->renderer = $renderer;
    }

    /**
     * Get the name of this layout
     *
     * @return string
     */
    public function getName(): string
    {
        return static::NAME;
    }

    /**
     * Create attributes for this layout
     *
     * @param Attributes $attributes
     *
     * @return void
     */
    protected function createAttributes(Attributes $attributes): void
    {
        $attributes->get('class')->addValue(['item-layout', 'default-item-layout']);
    }

    /**
     * Get attributes to apply to the container the layout is added to
     *
     * @return Attributes
     */
    public function getAttributes(): Attributes
    {
        $attributes = new Attributes();
        $this->createAttributes($attributes);
        $this->renderer->assembleAttributes($this->item, $attributes, $this->getName());

        return $attributes;
    }

    /**
     * Create a container for the visual representation of the item
     *
     * @return HtmlDocument
     */
    protected function createVisual(): HtmlDocument
    {
        return new HtmlElement('div', Attributes::create(['class' => static::VISUAL]));
    }

    /**
     * Assemble the visual representation of the item in the given container
     *
     * @return void
     */
    protected function assembleVisual(HtmlDocument $container)
    {
        $this->renderer->assembleVisual($this->item, $container, $this->getName());
    }

    /**
     * Create, assemble and register the visual representation of the item
     *
     * @param HtmlDocument $container
     *
     * @return void
     */
    protected function registerVisual(HtmlDocument $container): void
    {
        $visual = $this->createVisual();

        $this->createBefore(static::VISUAL, $container);

        $this->assembleVisual($visual);
        if ($visual->isEmpty()) {
            return;
        }

        $container->addHtml($visual);

        $this->createAfter(static::VISUAL, $container);
    }

    /**
     * Create a container for the main content of the item
     *
     * @return HtmlDocument
     */
    protected function createMain(): HtmlDocument
    {
        return new HtmlElement('div', Attributes::create(['class' => static::MAIN]));
    }

    /**
     * Assemble the main content of the item in the given container
     *
     * @param HtmlDocument $container
     *
     * @return void
     */
    protected function assembleMain(HtmlDocument $container): void
    {
        $this->registerHeader($container);
        $this->registerCaption($container);
    }

    /**
     * Create, assemble and register the main content of the item
     *
     * @param HtmlDocument $container
     *
     * @return void
     */
    protected function registerMain(HtmlDocument $container): void
    {
        $main = $this->createMain();

        $this->createBefore(static::MAIN, $container);

        $this->assembleMain($main);

        $container->addHtml($main);

        $this->createAfter(static::MAIN, $container);
    }

    /**
     * Create a container for the header of the item
     *
     * @return HtmlDocument
     */
    protected function createHeader(): HtmlDocument
    {
        return new HtmlElement('header');
    }

    /**
     * Assemble the header of the item in the given container
     *
     * @param HtmlDocument $container
     *
     * @return void
     */
    protected function assembleHeader(HtmlDocument $container): void
    {
        $this->registerTitle($container);
        $this->registerExtendedInfo($container);
    }

    /**
     * Create, assemble and register the header of the item
     *
     * @param HtmlDocument $container
     *
     * @return void
     */
    protected function registerHeader(HtmlDocument $container): void
    {
        $header = $this->createHeader();

        $this->createBefore(static::HEADER, $container);

        $this->assembleHeader($header);

        $container->addHtml($header);

        $this->createAfter(static::HEADER, $container);
    }

    /**
     * Create a container for the footer of the item
     *
     * @return HtmlDocument
     */
    protected function createFooter(): HtmlDocument
    {
        return new HtmlElement('footer');
    }

    /**
     * Assemble the footer of the item in the given container
     *
     * @param HtmlDocument $container
     *
     * @return void
     */
    protected function assembleFooter(HtmlDocument $container): void
    {
        $this->renderer->assembleFooter($this->item, $container, $this->getName());
    }

    /**
     * Create, assemble and register the footer of the item
     *
     * @param HtmlDocument $container
     *
     * @return void
     */
    protected function registerFooter(HtmlDocument $container): void
    {
        $footer = $this->createFooter();

        $this->createBefore(static::FOOTER, $container);

        $this->assembleFooter($footer);
        if ($footer->isEmpty()) {
            return;
        }

        $container->addHtml($footer);

        $this->createAfter(static::FOOTER, $container);
    }

    /**
     * Create a container for the caption of the item
     *
     * @return HtmlDocument
     */
    protected function createCaption(): HtmlDocument
    {
        return new HtmlElement('section', Attributes::create(['class' => static::CAPTION]));
    }

    /**
     * Assemble the caption of the item in the given container
     *
     * @param HtmlDocument $container
     *
     * @return void
     */
    protected function assembleCaption(HtmlDocument $container): void
    {
        $this->renderer->assembleCaption($this->item, $container, $this->getName());
    }

    /**
     * Create, assemble and register the caption of the item
     *
     * @param HtmlDocument $container
     *
     * @return void
     */
    protected function registerCaption(HtmlDocument $container): void
    {
        $caption = $this->createCaption();

        $this->createBefore(static::CAPTION, $container);

        $this->assembleCaption($caption);

        $container->addHtml($caption);

        $this->createAfter(static::CAPTION, $container);
    }

    /**
     * Create a container for the title of the item
     *
     * @return HtmlDocument
     */
    protected function createTitle(): HtmlDocument
    {
        return new HtmlElement('div', Attributes::create(['class' => static::TITLE]));
    }

    /**
     * Assemble the title of the item in the given container
     *
     * @param HtmlDocument $container
     *
     * @return void
     */
    protected function assembleTitle(HtmlDocument $container): void
    {
        $this->renderer->assembleTitle($this->item, $container, $this->getName());
    }

    /**
     * Create, assemble and register the title of the item
     *
     * @param HtmlDocument $container
     *
     * @return void
     */
    protected function registerTitle(HtmlDocument $container): void
    {
        $title = $this->createTitle();

        $this->createBefore(static::TITLE, $container);

        $this->assembleTitle($title);

        $container->addHtml($title);

        $this->createAfter(static::TITLE, $container);
    }

    /**
     * Create a container for the extended information of the item
     *
     * @return HtmlDocument
     */
    protected function createExtendedInfo(): HtmlDocument
    {
        return new HtmlElement('div', Attributes::create(['class' => static::EXTENDED_INFO]));
    }

    /**
     * Assemble the extended information of the item in the given container
     *
     * @param HtmlDocument $container
     *
     * @return void
     */
    protected function assembleExtendedInfo(HtmlDocument $container): void
    {
        $this->renderer->assembleExtendedInfo($this->item, $container, $this->getName());
    }

    /**
     * Create, assemble and register the extended information of the item
     *
     * @param HtmlDocument $container
     *
     * @return void
     */
    protected function registerExtendedInfo(HtmlDocument $container): void
    {
        $info = $this->createExtendedInfo();

        $this->createBefore(static::EXTENDED_INFO, $container);

        $this->assembleExtendedInfo($info);
        if ($info->isEmpty()) {
            return;
        }

        $container->addHtml($info);

        $this->createAfter(static::EXTENDED_INFO, $container);
    }

    /**
     * Render a custom section before the given one
     *
     * @param SECTION $section Identifier of the target section
     * @param string $customSection Identifier for the custom section
     *
     * @return void
     */
    public function before(string $section, string $customSection): void
    {
        $this->before[$section] = $customSection;
    }

    /**
     * Add a custom section before the given one
     *
     * @param SECTION $section Identifier of the target section
     * @param HtmlDocument $container The container to add the custom section to
     *
     * @return void
     */
    protected function createBefore(string $section, HtmlDocument $container): void
    {
        if (isset($this->before[$section])) {
            $customSection = new HtmlElement('div', Attributes::create(['class' => $this->before[$section]]));
            if ($this->renderer->assemble($this->item, $this->before[$section], $customSection, $this->getName())) {
                $container->addHtml($customSection);
            }
        }
    }

    /**
     * Render a custom section after the given one
     *
     * @param SECTION $section Identifier of the target section
     * @param string $customSection Identifier for the custom section
     *
     * @return void
     */
    public function after(string $section, string $customSection): void
    {
        $this->after[$section] = $customSection;
    }

    /**
     * Add a custom section after the given one
     *
     * @param SECTION $section Identifier of the target section
     * @param HtmlDocument $container The container to add the custom section to
     *
     * @return void
     */
    protected function createAfter(string $section, HtmlDocument $container): void
    {
        if (isset($this->after[$section])) {
            $customSection = new HtmlElement('div', Attributes::create(['class' => $this->after[$section]]));
            if ($this->renderer->assemble($this->item, $this->after[$section], $customSection, $this->getName())) {
                $container->addHtml($customSection);
            }
        }
    }

    /**
     * Assemble the layout
     *
     * @return void
     */
    protected function assemble(): void
    {
        $this->registerVisual($this);
        $this->registerMain($this);
    }
}
