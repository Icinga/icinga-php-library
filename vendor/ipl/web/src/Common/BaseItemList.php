<?php

namespace ipl\Web\Common;

use InvalidArgumentException;
use ipl\Html\BaseHtmlElement;
use ipl\Orm\ResultSet;
use ipl\Stdlib\BaseFilter;
use ipl\Web\Widget\EmptyStateBar;

/**
 * Base class for item lists
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

    protected function assemble(): void
    {
        $itemClass = $this->getItemClass();
        foreach ($this->data as $data) {
            /** @var BaseListItem|BaseTableRowItem $item */
            $item = new $itemClass($data, $this);
            $this->emit(self::BEFORE_ITEM_ADD, [$item, $data]);
            $this->addHtml($item);
            $this->emit(self::ON_ITEM_ADD, [$item, $data]);
        }

        if ($this->isEmpty()) {
            $this->setTag('div');
            $this->addHtml(new EmptyStateBar(t('No items found.')));
        }
    }
}
