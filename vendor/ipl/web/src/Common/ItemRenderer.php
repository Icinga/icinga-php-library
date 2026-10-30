<?php

namespace ipl\Web\Common;

use ipl\Html\Attributes;
use ipl\Html\HtmlDocument;

/**
 * Interface for item renderers
 *
 * @template Item
 */
interface ItemRenderer
{
    /**
     * Assemble the attributes for an item
     *
     * @param Item $item The item to render
     * @param Attributes $attributes
     * @param string $layout The name of the layout
     *
     * @return void
     */
    public function assembleAttributes($item, Attributes $attributes, string $layout): void;

    /**
     * Assemble a visual representation of an item
     *
     * @param Item $item The item to render
     * @param HtmlDocument $visual The container to add the result to
     * @param string $layout The name of the layout
     *
     * @return void
     */
    public function assembleVisual($item, HtmlDocument $visual, string $layout): void;

    /**
     * Assemble a caption to describe an item
     *
     * @param Item $item The item to render
     * @param HtmlDocument $caption The container to add the result to
     * @param string $layout The name of the layout
     *
     * @return void
     */
    public function assembleCaption($item, HtmlDocument $caption, string $layout): void;

    /**
     * Assemble a footer with additional information for an item
     *
     * @param Item $item The item to render
     * @param HtmlDocument $footer The container to add the result to
     * @param string $layout The name of the layout
     *
     * @return void
     */
    public function assembleFooter($item, HtmlDocument $footer, string $layout): void;

    /**
     * Assemble a title for an item
     *
     * @param Item $item The item to render
     * @param HtmlDocument $title The container to add the result to
     * @param string $layout The name of the layout
     *
     * @return void
     */
    public function assembleTitle($item, HtmlDocument $title, string $layout): void;

    /**
     * Assemble extended information for an item
     *
     * @param Item $item The item to render
     * @param HtmlDocument $info The container to add the result to
     * @param string $layout The name of the layout
     *
     * @return void
     */
    public function assembleExtendedInfo($item, HtmlDocument $info, string $layout): void;

    /**
     * Assemble a custom section for an item
     *
     * @param Item $item The item to render
     * @param string $name The identifier of the section
     * @param HtmlDocument $element The container to add the result to
     * @param string $layout The name of the layout
     *
     * @return bool Whether to add the section to the layout
     */
    public function assemble($item, string $name, HtmlDocument $element, string $layout): bool;
}
