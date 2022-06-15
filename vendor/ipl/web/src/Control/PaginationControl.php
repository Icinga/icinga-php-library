<?php

namespace ipl\Web\Control;

use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Stdlib\Contract\Paginatable;
use ipl\Web\Compat\CompatForm;
use ipl\Web\Url;
use ipl\Web\Widget\Icon;

/**
 * The pagination control displays a list of links that point to different pages of the current view
 *
 * The default HTML markup (tag and attributes) for the paginator look like the following:
 * <div class="pagination-control" role="navigation">...</div>
 */
class PaginationControl extends BaseHtmlElement
{
    /** @var int Default maximum number of items which should be shown per page */
    protected $defaultPageSize;

    /** @var string Name of the URL parameter which stores the current page number */
    protected $pageParam = 'page';

    /** @var string Name of the URL parameter which holds the page size. If given, overrides {@link $defaultPageSize} */
    protected $pageSizeParam = 'limit';

    /** @var string */
    protected $pageSpacer = '…';

    /** @var Paginatable The pagination adapter which handles the underlying data source */
    protected $paginatable;

    /** @var Url The URL to base off pagination URLs */
    protected $url;

    /** @var int Cache for the total number of items */
    private $totalCount;

    protected $tag = 'div';

    protected $defaultAttributes = [
        'class' => 'pagination-control',
        'role'  => 'navigation'
    ];

    /**
     * Create a pagination control
     *
     * @param Paginatable $paginatable The paginatable
     * @param Url         $url         The URL to base off paging URLs
     */
    public function __construct(Paginatable $paginatable, Url $url)
    {
        $this->paginatable = $paginatable;
        $this->url = $url;
    }

    /**
     * Set the URL to base off paging URLs
     *
     * @param Url $url
     *
     * @return $this
     */
    public function setUrl(Url $url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get the default page size
     *
     * @return int
     */
    public function getDefaultPageSize()
    {
        return $this->defaultPageSize ?: LimitControl::DEFAULT_LIMIT;
    }

    /**
     * Set the default page size
     *
     * @param int $defaultPageSize
     *
     * @return $this
     */
    public function setDefaultPageSize($defaultPageSize)
    {
        $this->defaultPageSize = $defaultPageSize;

        return $this;
    }

    /**
     * Get the name of the URL parameter which stores the current page number
     *
     * @return string
     */
    public function getPageParam()
    {
        return $this->pageParam;
    }

    /**
     * Set the name of the URL parameter which stores the current page number
     *
     * @param string $pageParam
     *
     * @return $this
     */
    public function setPageParam($pageParam)
    {
        $this->pageParam = $pageParam;

        return $this;
    }

    /**
     * Get the name of the URL parameter which stores the page size
     *
     * @return string
     */
    public function getPageSizeParam()
    {
        return $this->pageSizeParam;
    }
    /**
     * Set the name of the URL parameter which stores the page size
     *
     * @param string $pageSizeParam
     *
     * @return $this
     */
    public function setPageSizeParam($pageSizeParam)
    {
        $this->pageSizeParam = $pageSizeParam;

        return $this;
    }

    /**
     * Get the total number of items
     *
     * @return int
     */
    public function getTotalCount()
    {
        if ($this->totalCount === null) {
            $this->totalCount = $this->paginatable->count();
        }

        return $this->totalCount;
    }

    /**
     * Get the current page number
     *
     * @return int
     */
    public function getCurrentPageNumber()
    {
        return (int) $this->url->getParam($this->pageParam, 1);
    }

    /**
     * Get the configured page size
     *
     * @return int
     */
    public function getPageSize()
    {
        return (int) $this->url->getParam($this->pageSizeParam, $this->getDefaultPageSize());
    }

    /**
     * Get the total page count
     *
     * @return int
     */
    public function getPageCount()
    {
        $pageSize = $this->getPageSize();

        if ($pageSize === 0) {
            return 0;
        }

        if ($pageSize < 0) {
            return 1;
        }

        return (int) ceil($this->getTotalCount() / $pageSize);
    }

    /**
     * Get the limit
     *
     * Use this method to set the LIMIT part of a query for fetching the current page.
     *
     * @return int If the page size is infinite, -1 will be returned
     */
    public function getLimit()
    {
        $pageSize = $this->getPageSize();

        return $pageSize < 0 ? -1 : $pageSize;
    }

    /**
     * Get the offset
     *
     * Use this method to set the OFFSET part of a query for fetching the current page.
     *
     * @return int
     */
    public function getOffset()
    {
        $currentPageNumber = $this->getCurrentPageNumber();
        $pageSize = $this->getPageSize();

        return $currentPageNumber <= 1 ? 0 : ($currentPageNumber - 1) * $pageSize;
    }

    /**
     * Apply limit and offset on the paginatable
     *
     * @return $this
     */
    public function apply()
    {
        $this->paginatable->limit($this->getLimit());
        $this->paginatable->offset($this->getOffset());

        return $this;
    }

    /**
     * Create a URL for paging from the given page number
     *
     * @param int $pageNumber The page number
     * @param int $pageSize   The number of items per page. If you want to stick to the defaults,
     *                        don't set this parameter
     *
     * @return Url
     */
    public function createUrl($pageNumber, $pageSize = null)
    {
        $params = [$this->getPageParam() => $pageNumber];

        if ($pageSize !== null) {
            $params[$this->getPageSizeParam()] = $pageSize;
        }

        return $this->url->with($params);
    }

    /**
     * Get the first item number of the given page
     *
     * @param int $pageNumber
     *
     * @return int
     */
    protected function getFirstItemNumberOfPage($pageNumber)
    {
        return ($pageNumber - 1) * $this->getPageSize() + 1;
    }

    /**
     * Get the last item number of the given page
     *
     * @param int $pageNumber
     *
     * @return int
     */
    protected function getLastItemNumberOfPage($pageNumber)
    {
        return min($pageNumber * $this->getPageSize(), $this->getTotalCount());
    }

    /**
     * Create the label for the given page number
     *
     * @param int $pageNumber
     *
     * @return string
     */
    protected function createLabel($pageNumber)
    {
        return sprintf(
            $this->translate('Show items %u to %u of %u'),
            $this->getFirstItemNumberOfPage($pageNumber),
            $this->getLastItemNumberOfPage($pageNumber),
            $this->getTotalCount()
        );
    }

    /**
     * Create and return the previous page item
     *
     * @return BaseHtmlElement
     */
    protected function createPreviousPageItem()
    {
        $prevIcon = new Icon('angle-left');

        $currentPageNumber = $this->getCurrentPageNumber();

        if ($currentPageNumber > 1) {
            $prevItem = Html::tag('li', ['class' => 'nav-item']);

            $prevItem->add(Html::tag(
                'a',
                [
                    'class' => 'previous-page',
                    'href'  => $this->createUrl($currentPageNumber - 1),
                    'title' => $this->createLabel($currentPageNumber - 1)
                ],
                $prevIcon
            ));
        } else {
            $prevItem = Html::tag(
                'li',
                [
                    'aria-hidden' => true,
                    'class'       => 'nav-item disabled'
                ]
            );

            $prevItem->add(Html::tag('span', ['class' => 'previous-page'], $prevIcon));
        }

        return $prevItem;
    }

    /**
     * Create and return the next page item
     *
     * @return BaseHtmlElement
     */
    protected function createNextPageItem()
    {
        $nextIcon = new Icon('angle-right');

        $currentPageNumber = $this->getCurrentPageNumber();

        if ($currentPageNumber < $this->getPageCount()) {
            $nextItem = Html::tag('li', ['class' => 'nav-item']);

            $nextItem->add(Html::tag(
                'a',
                [
                    'class' => 'next-page',
                    'href'  => $this->createUrl($currentPageNumber + 1),
                    'title' => $this->createLabel($currentPageNumber + 1)
                ],
                $nextIcon
            ));
        } else {
            $nextItem = Html::tag(
                'li',
                [
                    'aria-hidden' => true,
                    'class'       => 'nav-item disabled'
                ]
            );

            $nextItem->add(Html::tag('span', ['class' => 'next-page'], $nextIcon));
        }

        return $nextItem;
    }

    /** @TODO(el): Use ipl-translation when it's ready instead */
    private function translate($message)
    {
        return $message;
    }

    /**
     * Create and return the first page item
     *
     * @return BaseHtmlElement
     */
    protected function createFirstPageItem()
    {
        $currentPageNumber = $this->getCurrentPageNumber();

        $url = clone $this->url;

        $firstItem = Html::tag('li', ['class' => 'nav-item']);

        if ($currentPageNumber === 1) {
            $firstItem->addAttributes(['class' => 'disabled']);
            $firstItem->add(Html::tag(
                'span',
                ['class' => 'first-page'],
                $this->getFirstItemNumberOfPage(1)
            ));
        } else {
            $firstItem->add(Html::tag(
                'a',
                [
                    'class' => 'first-page',
                    'href' => $url->remove(['page'])->getAbsoluteUrl(),
                    'title' => $this->createLabel(1)
                ],
                $this->getFirstItemNumberOfPage(1)
            ));
        }

        return $firstItem;
    }

    /**
     * Create and return the last page item
     *
     * @return BaseHtmlElement
     */
    protected function createLastPageItem()
    {
        $currentPageNumber = $this->getCurrentPageNumber();
        $lastItem = Html::tag('li', ['class' => 'nav-item']);

        if ($currentPageNumber === $this->getPageCount()) {
            $lastItem->addAttributes(['class' => 'disabled']);
            $lastItem->add(Html::tag(
                'span',
                ['class' => 'last-page'],
                $this->getPageCount()
            ));
        } else {
            $lastItem->add(Html::tag(
                'a',
                [
                    'class' => 'last-page',
                    'href' => $this->url->setParam('page', $this->getPageCount()),
                    'title' => $this->createLabel($this->getPageCount())
                ],
                $this->getPageCount()
            ));
        }

        return $lastItem;
    }

    /**
     * Create and return the page selector item
     *
     * @return BaseHtmlElement
     */
    protected function createPageSelectorItem()
    {
        $currentPageNumber = $this->getCurrentPageNumber();

        $form = new CompatForm($this->url);
        $form->addAttributes(['class' => 'inline']);
        $form->setMethod('GET');

        $select = Html::tag('select', [
            'name' => $this->getPageParam(),
            'class' => 'autosubmit',
            'title' => t('Go to page …')
        ]);

        if (isset($currentPageNumber)) {
            if ($currentPageNumber === 1 || $currentPageNumber === $this->getPageCount()) {
                $select->add(Html::tag('option', ['disabled' => '', 'selected' => ''], '…'));
            }
        }

        foreach (range(2, $this->getPageCount() - 1) as $page) {
            $option = Html::tag('option', [
                'value' => $page
            ], $page);

            if ($page == $currentPageNumber) {
                $option->addAttributes(['selected' => '']);
            }

            $select->add($option);
        }

        $form->add($select);

        $pageSelectorItem = Html::tag('li', $form);

        return $pageSelectorItem;
    }

    protected function assemble()
    {
        if ($this->getPageCount() < 2) {
            return;
        }

        // Accessibility info
        $this->add(Html::tag(
            'h2',
            [
                'class'    => 'sr-only',
                'tabindex' => '-1'
            ],
            $this->translate('Pagination')
        ));

        $paginator = Html::tag('ul', ['class' => 'tab-nav nav']);

        $paginator->add([
            $this->createFirstPageItem(),
            $this->createPreviousPageItem(),
            $this->createPageSelectorItem(),
            $this->createNextPageItem(),
            $this->createLastPageItem()
        ]);

        $this->add($paginator);
    }
}
