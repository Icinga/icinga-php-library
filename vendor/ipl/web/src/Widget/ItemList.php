<?php

namespace ipl\Web\Widget;

use InvalidArgumentException;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\I18n\Translation;
use ipl\Orm\ResultSet;
use ipl\Web\Common\ItemRenderer;
use ipl\Web\Layout\ItemLayout;

/**
 * ItemList
 *
 * @template Result of object
 * @template Item of object = Result
 */
class ItemList extends BaseHtmlElement
{
    use Translation;

    /** @var string Emitted while assembling the list after adding each list item */
    public const ON_ITEM_ADD = 'item-added';

    /** @var string Emitted while assembling the list before adding each list item */
    public const BEFORE_ITEM_ADD = 'before-item-add';

    /** @var ResultSet|iterable<Result> */
    protected $data;

    /** @var ItemRenderer|callable */
    protected $itemRenderer;

    /** @var string */
    private $itemLayoutClass = ItemLayout::class;

    /** @var ?ValidHtml Message to show if the list is empty */
    protected $emptyStateMessage;

    /** @var array<string, mixed> */
    protected $baseAttributes = [
        'class'                         => 'item-list',
        'data-base-target'              => '_next',
        'data-pdfexport-page-breaks-at' => '.list-item'
    ];

    protected $tag = 'ul';

    /**
     * Create a new item list
     *
     * @param ResultSet|iterable<Result> $data
     * @param ItemRenderer<Item>|callable $itemRenderer If a callable is passed,
     *                                                  it must accept an item as first argument
     */
    public function __construct($data, $itemRenderer)
    {
        if (! is_iterable($data)) {
            throw new InvalidArgumentException('Data must be an array or an instance of Traversable');
        }

        $this->data = $data;
        $this->itemRenderer = $itemRenderer;

        $this->addAttributes($this->baseAttributes);

        $this->init();
    }

    /**
     * Initialize the item list
     *
     * If you want to adjust the item list after construction, override this method.
     */
    protected function init(): void
    {
    }

    /**
     * Set the layout class for the items
     *
     * @param string $class Must be an instance of the previously set item layout class
     *
     * @return $this
     *
     * @throws InvalidArgumentException If the class is not a subclass of {@see ItemLayout}
     */
    public function setItemLayoutClass(string $class): self
    {
        if ($class !== $this->itemLayoutClass && ! is_subclass_of($class, $this->itemLayoutClass)) {
            throw new InvalidArgumentException("Class $class must be a subclass of " . $this->itemLayoutClass);
        }

        $this->itemLayoutClass = $class;

        return $this;
    }

    /**
     * Get the layout for the given item
     *
     * @param Item $item
     *
     * @return ItemLayout
     */
    public function getItemLayout($item): ItemLayout
    {
        return new $this->itemLayoutClass($item, $this->getItemRenderer($item));
    }

    /**
     * Get message to show if the list is empty
     *
     * @return ValidHtml
     */
    public function getEmptyStateMessage(): ValidHtml
    {
        if ($this->emptyStateMessage === null) {
            return new Text($this->translate('No items found.'));
        }

        return $this->emptyStateMessage;
    }

    /**
     * Set message to show if the list is empty
     *
     * @param mixed $message If empty, the default message is used
     *
     * @return $this
     */
    public function setEmptyStateMessage(mixed $message): self
    {
        if (empty($message)) {
            $this->emptyStateMessage = null;
        } else {
            $this->emptyStateMessage = Html::wantHtml($message);
        }

        return $this;
    }

    /**
     * Get renderer for the given item
     *
     * @param Item $item
     *
     * @return ItemRenderer<Item>
     */
    protected function getItemRenderer($item): ItemRenderer
    {
        if (is_callable($this->itemRenderer)) {
            return call_user_func($this->itemRenderer, $item);
        } elseif (is_string($this->itemRenderer)) {
            $this->itemRenderer = new $this->itemRenderer();
        }

        return $this->itemRenderer;
    }

    /**
     * Create a list item for the given data
     *
     * @param Result $data
     *
     * @return ListItem<Item>
     */
    protected function createListItem(object $data)
    {
        return new ListItem($data, $this);
    }

    protected function assemble(): void
    {
        /** @var Result $data */
        foreach ($this->data as $data) {
            $item = $this->createListItem($data);
            $this->emit(self::BEFORE_ITEM_ADD, [$item, $data]);
            $this->addHtml($item);
            $this->emit(self::ON_ITEM_ADD, [$item, $data]);
        }

        if ($this->isEmpty()) {
            $this->setTag('div');
            $this->addHtml(new EmptyState($this->getEmptyStateMessage()));
        }
    }
}
