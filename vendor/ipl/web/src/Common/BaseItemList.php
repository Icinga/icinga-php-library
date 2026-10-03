<?php

namespace ipl\Web\Common;

use InvalidArgumentException;
use ipl\Html\BaseHtmlElement;
use ipl\Orm\ResultSet;
use ipl\Stdlib\BaseFilter;
use ipl\Web\Widget\EmptyStateBar;
use ipl\Web\Widget\ItemList;

/**
 * Base class for item lists
 *
 * @deprecated Use {@see ItemList} instead
 */
abstract class BaseItemList extends BaseHtmlElement
{
    use BaseFilter;

    /** @var string Emitted while assembling the list after adding each list item */
    public const ON_ITEM_ADD = 'item-added';

    /** @var string Emitted while assembling the list before adding each list item */
    public const BEFORE_ITEM_ADD = 'before-item-add';

    /** @var array<string, mixed> */
    protected $baseAttributes = [
        'class'                         => ['item-list', 'default-layout'],
        'data-base-target'              => '_next',
        'data-pdfexport-page-breaks-at' => '.list-item'
    ];

    /** @var ResultSet|iterable<object> */
    protected $data;

    protected $tag = 'ul';

    /** @var ?string Message to show if the list is empty */
    protected $emptyStateMessage;

    /**
     * Create a new item  list
     *
     * @param ResultSet|iterable<object> $data Data source of the list
     */
    public function __construct($data)
    {
        if (! is_iterable($data)) {
            throw new InvalidArgumentException('Data must be an array or an instance of Traversable');
        }

        $this->data = $data;

        $this->addAttributes($this->baseAttributes);

        $this->init();
    }

    abstract protected function getItemClass(): string;

    /**
     * Initialize the item list
     *
     * If you want to adjust the item list after construction, override this method.
     */
    protected function init(): void
    {
    }

    /**
     * Create a list item for the given data
     *
     * @param object $data
     *
     * @return BaseListItem|BaseTableRowItem
     */
    protected function createListItem(object $data)
    {
        $className = $this->getItemClass();

        return new $className($data, $this);
    }

    /**
     * Get message to show if the list is empty
     *
     * @return string
     */
    public function getEmptyStateMessage(): string
    {
        if ($this->emptyStateMessage === null) {
            return t('No items found.');
        }

        return $this->emptyStateMessage;
    }

    /**
     * Set message to show if the list is empty
     *
     * @param string $message
     *
     * @return $this
     */
    public function setEmptyStateMessage(string $message): self
    {
        $this->emptyStateMessage = $message;

        return $this;
    }

    protected function assemble(): void
    {
        foreach ($this->data as $data) {
            $item = $this->createListItem($data);
            $this->emit(self::BEFORE_ITEM_ADD, [$item, $data]);
            $this->addHtml($item);
            $this->emit(self::ON_ITEM_ADD, [$item, $data]);
        }

        if ($this->isEmpty()) {
            $this->setTag('div');
            $this->addHtml(new EmptyStateBar($this->getEmptyStateMessage()));
        }
    }
}
